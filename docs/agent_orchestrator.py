#!/usr/bin/env python3
"""
Agent Orchestrator

Run gemini/auggie CLI agents in parallel with a concurrency cap, timeouts, retries,
and structured logging of outputs and metadata.

Usage examples (see docs/agent-orchestrator-usage.md for details):
  python scripts/agent_orchestrator.py run \
    --tasks tasks.json --concurrency 4 --timeout 120 --retries 0

  python scripts/agent_orchestrator.py collect \
    --run-dir .agent-runs/2025-09-18_12-00-00
"""
from __future__ import annotations

import argparse
import datetime as dt
import json
import os
import signal
import sys
import time
import uuid
from pathlib import Path
from subprocess import Popen, PIPE
from typing import Dict, List, Optional, Any

DEFAULT_RUNS_ROOT = Path(".agent-runs")
JOBS_DIR_NAME = "jobs"
LEDGER_FILENAME = "run_ledger.jsonl"
SUMMARY_FILENAME = "summary.json"

class Job:
    def __init__(self, idx: int, agent: str, prompt: str, tag: Optional[str] = None, timeout: int = 0, retries: int = 0):
        self.idx = idx
        self.agent = agent
        self.prompt = prompt
        self.tag = tag or f"{agent}-{idx}-{uuid.uuid4().hex[:6]}"
        self.timeout = max(0, int(timeout))
        self.retries = max(0, int(retries))
        self.attempt = 0
        self.process: Optional[Popen] = None
        self.start_ts: Optional[float] = None
        self.end_ts: Optional[float] = None
        self.exit_code: Optional[int] = None
        self.status: str = "PENDING"  # PENDING|RUNNING|SUCCEEDED|FAILED|TIMEOUT|KILLED
        self.out_path: Optional[Path] = None
        self.meta_path: Optional[Path] = None

    def to_meta(self) -> Dict[str, Any]:
        return {
            "idx": self.idx,
            "tag": self.tag,
            "agent": self.agent,
            "prompt": self.prompt,
            "timeout": self.timeout,
            "retries": self.retries,
            "attempt": self.attempt,
            "start_ts": self.start_ts,
            "end_ts": self.end_ts,
            "duration_sec": (self.end_ts - self.start_ts) if self.start_ts and self.end_ts else None,
            "exit_code": self.exit_code,
            "status": self.status,
            "out_path": str(self.out_path) if self.out_path else None,
        }


def now_str() -> str:
    return dt.datetime.now().strftime("%Y-%m-%d_%H-%M-%S")


def ensure_dir(path: Path) -> Path:
    path.mkdir(parents=True, exist_ok=True)
    return path


def write_json(path: Path, data: Any) -> None:
    path.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")


def append_jsonl(path: Path, data: Any) -> None:
    with path.open("a", encoding="utf-8") as f:
        f.write(json.dumps(data, ensure_ascii=False) + "\n")


def start_job(job: Job, jobs_dir: Path) -> None:
    job.attempt += 1
    job.status = "RUNNING"
    job.start_ts = time.time()
    job.out_path = jobs_dir / f"{job.tag}.out"
    job.meta_path = jobs_dir / f"{job.tag}.json"

    # Launch the agent process; capture stdout/stderr to file, avoid shell quoting issues.
    out_f = job.out_path.open("wb")
    # Using list args avoids shell quoting; agent CLIs accept -p <prompt>.
    job.process = Popen([job.agent, "-p", job.prompt], stdout=out_f, stderr=out_f)


def poll_and_manage(job: Job) -> bool:
    """Poll process; return True if completed (success/fail/timeout), False if still running."""
    if job.process is None:
        return True
    rc = job.process.poll()
    now = time.time()
    if job.timeout and job.start_ts and (now - job.start_ts) > job.timeout and rc is None:
        # Timeout -> kill
        try:
            job.process.kill()
        except Exception:
            pass
        job.end_ts = time.time()
        job.exit_code = None
        job.status = "TIMEOUT"
        return True
    if rc is None:
        return False
    # Completed
    job.end_ts = time.time()
    job.exit_code = rc
    job.status = "SUCCEEDED" if rc == 0 else "FAILED"
    return True


def finalize_job(job: Job, ledger_path: Path) -> None:
    # Ensure meta is written at each completion
    if job.meta_path:
        write_json(job.meta_path, job.to_meta())
    append_jsonl(ledger_path, job.to_meta())


def run_tasks(tasks: List[Dict[str, Any]], runs_root: Path, concurrency: int, timeout: int, retries: int) -> Path:
    run_dir = ensure_dir(runs_root / now_str())
    jobs_dir = ensure_dir(run_dir / JOBS_DIR_NAME)
    ledger_path = run_dir / LEDGER_FILENAME

    jobs: List[Job] = [
        Job(i, t["agent"], t["prompt"], t.get("tag"), timeout=timeout or int(t.get("timeout", 0)), retries=retries or int(t.get("retries", 0)))
        for i, t in enumerate(tasks)
    ]

    # Save the input spec
    write_json(run_dir / "tasks.json", tasks)

    in_flight: List[Job] = []
    pending: List[Job] = jobs.copy()
    finished: List[Job] = []

    def maybe_launch():
        while pending and len(in_flight) < concurrency:
            j = pending.pop(0)
            start_job(j, jobs_dir)
            in_flight.append(j)

    maybe_launch()

    while in_flight or pending:
        # Poll running jobs
        for j in list(in_flight):
            done = poll_and_manage(j)
            if done:
                # If failed/timeout and has retries left, relaunch
                if j.status in ("FAILED", "TIMEOUT") and j.attempt <= j.retries:
                    start_job(j, jobs_dir)
                else:
                    finalize_job(j, ledger_path)
                    in_flight.remove(j)
                    finished.append(j)
        # Launch more if capacity
        maybe_launch()
        time.sleep(0.2)

    # Write summary
    summary = {
        "run_dir": str(run_dir),
        "total": len(jobs),
        "by_status": {
            s: sum(1 for j in jobs if j.status == s)
            for s in ["SUCCEEDED", "FAILED", "TIMEOUT", "RUNNING", "PENDING"]
        },
        "concurrency": concurrency,
        "timeout": timeout,
        "retries": retries,
    }
    write_json(run_dir / SUMMARY_FILENAME, summary)
    return run_dir


def cmd_run(args: argparse.Namespace) -> None:
    runs_root = Path(args.run_dir or DEFAULT_RUNS_ROOT)
    ensure_dir(runs_root)

    # Load tasks
    tasks_path = Path(args.tasks)
    tasks = json.loads(tasks_path.read_text(encoding="utf-8"))
    if not isinstance(tasks, list):
        print("ERROR: tasks.json must be a JSON array of {agent, prompt, [tag, timeout, retries]}", file=sys.stderr)
        sys.exit(2)

    # Basic validation
    for i, t in enumerate(tasks):
        if not isinstance(t, dict) or "agent" not in t or "prompt" not in t:
            print(f"ERROR: tasks[{i}] must contain 'agent' and 'prompt'", file=sys.stderr)
            sys.exit(2)
        if t["agent"] not in ("gemini", "auggie"):
            print(f"ERROR: tasks[{i}].agent must be 'gemini' or 'auggie'", file=sys.stderr)
            sys.exit(2)

    run_dir = run_tasks(tasks, runs_root, args.concurrency, args.timeout, args.retries)
    print(str(run_dir))


def cmd_collect(args: argparse.Namespace) -> None:
    run_dir = Path(args.run_dir)
    jobs_dir = run_dir / JOBS_DIR_NAME
    combined = []
    for out_file in sorted(jobs_dir.glob("*.out")):
        try:
            text = out_file.read_text(encoding="utf-8", errors="replace")
        except Exception as e:
            text = f"<read_error: {e}>"
        combined.append({
            "tag": out_file.stem,
            "output_path": str(out_file),
            "output": text,
        })
    out_path = run_dir / "combined.json"
    write_json(out_path, combined)
    print(str(out_path))


def build_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(description="Orchestrate gemini/auggie CLI agents in parallel")
    sub = p.add_subparsers(dest="cmd", required=True)

    pr = sub.add_parser("run", help="Run tasks with concurrency, timeouts, retries")
    pr.add_argument("--tasks", required=True, help="Path to tasks.json (array of {agent,prompt,tag?,timeout?,retries?})")
    pr.add_argument("--concurrency", type=int, default=2, help="Max concurrent processes")
    pr.add_argument("--timeout", type=int, default=0, help="Per-task timeout in seconds (0 = no timeout)")
    pr.add_argument("--retries", type=int, default=0, help="Retries per task on failure/timeout")
    pr.add_argument("--run-dir", default=str(DEFAULT_RUNS_ROOT), help="Root directory for runs")
    pr.set_defaults(func=cmd_run)

    pc = sub.add_parser("collect", help="Aggregate outputs from a run directory")
    pc.add_argument("--run-dir", required=True, help="Run directory (e.g., .agent-runs/2025-09-18_12-00-00)")
    pc.set_defaults(func=cmd_collect)

    return p


def main(argv: Optional[List[str]] = None) -> None:
    parser = build_parser()
    args = parser.parse_args(argv)
    args.func(args)


if __name__ == "__main__":
    main()

