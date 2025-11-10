#!/usr/bin/env python3
"""
Spawn Sub-Agent (minimal v1)

Usage:
    python scripts/spawn_sub_agent.py auggie "YOUR PROMPT HERE"

Behavior:
- Creates ./subagent_runs/{job_id}/ with:
  - prompt.txt, status.json, output.jsonl, report.md (on success), run.log
- Spawns a detached worker subprocess that performs the agent run
- Immediately prints the job_id to stdout and exits 0
- Calls: auggie -p "PROMPT"

IMPORTANT: Sub-agents are STATELESS
- Sub-agents have NO memory of previous conversations or context
- Sub-agents CANNOT access the main agent's working memory
- Instructions must be COMPLETE and SELF-CONTAINED
- Provide ALL necessary context in the prompt or reference specific files to read
- Specify exact file paths for inputs and outputs
- Include step-by-step procedures, not just high-level goals

Example of good prompt:
    "Read the file confluence/docs/EXAMPLE.md. Extract all section headings and
    write them to a numbered list in subagent_runs/output.md. Use this format:
    1. [Heading text]
    2. [Heading text]"

Example of bad prompt:
    "Analyze the documentation we discussed earlier and create a summary."
    (Sub-agent has no memory of "earlier" and doesn't know which docs)

Env:
- Uses environment variables for credentials. No secrets in code.
"""
import argparse
import json
import os
import sys
import time
import uuid
from pathlib import Path
from datetime import datetime
import subprocess

ROOT = Path(__file__).resolve().parent
RUNS_DIR = ROOT / "subagent_runs"
SETTINGS_PATH = ROOT / "settings.json"


def now_iso() -> str:
    return datetime.utcnow().isoformat() + "Z"


def write_json(path: Path, data: dict):
    path.parent.mkdir(parents=True, exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2)


def load_settings() -> dict:
    if SETTINGS_PATH.exists():
        with open(SETTINGS_PATH, "r", encoding="utf-8") as f:
            return json.load(f)
    return {}


def init_job(agent: str, prompt: str) -> tuple[str, Path]:
    ts = time.strftime("%Y%m%d_%H%M%S")
    job_id = f"{ts}_{uuid.uuid4().hex[:6]}"
    job_dir = RUNS_DIR / job_id
    job_dir.mkdir(parents=True, exist_ok=True)

    # Persist prompt
    (job_dir / "prompt.txt").write_text(prompt, encoding="utf-8")

    # Initialize status and logs
    status = {
        "job_id": job_id,
        "agent": agent.lower(),
        "started_at": now_iso(),
        "status": "running",
        "exit_code": None,
        "duration_ms": 0,
    }
    write_json(job_dir / "status.json", status)
    (job_dir / "output.jsonl").write_text("{\"event\":\"start\"}\n", encoding="utf-8")
    (job_dir / "run.log").write_text(f"[{now_iso()}] Job {job_id} started for agent={agent}\n", encoding="utf-8")
    return job_id, job_dir


def spawn_worker(job_id: str, agent: str, job_dir: Path):
    """Spawn detached worker subprocess that handles the agent run."""
    log_f = open(job_dir / "run.log", "a", encoding="utf-8")
    cmd = [
        sys.executable,
        str(Path(__file__).resolve()),
        "--worker",
        "--job-id", job_id,
        "--agent", agent,
        "--job-dir", str(job_dir),
    ]
    # Detach and redirect stdout/stderr to run.log
    subprocess.Popen(
        cmd,
        stdout=log_f,
        stderr=log_f,
        stdin=subprocess.DEVNULL,
        cwd=str(ROOT),
        start_new_session=True,
        close_fds=True,
    )


def run_worker(job_id: str, agent: str, job_dir: Path) -> int:
    start = time.time()
    status_path = job_dir / "status.json"
    output_path = job_dir / "output.jsonl"
    report_path = job_dir / "report.md"
    prompt = (job_dir / "prompt.txt").read_text(encoding="utf-8")

    exit_code = 0
    err_msg = None

    try:
        agent_lc = agent.lower()
        if agent_lc == "auggie":
            # Call auggie CLI
            result = subprocess.run(
                ["auggie", "-p", prompt],
                capture_output=True,
                text=True,
                timeout=320,
                cwd=str(ROOT),
            )
            if result.returncode != 0:
                raise Exception(f"Auggie CLI failed: {result.stderr}")

            text = result.stdout.strip()
            report_path.write_text(text, encoding="utf-8")
            with open(output_path, "a", encoding="utf-8") as f:
                f.write(json.dumps({"event": "final", "report": "report.md"}) + "\n")
        else:
            raise ValueError(f"Unsupported agent: {agent}. Only 'auggie' is supported.")

    except Exception as e:
        exit_code = 1
        err_msg = f"Worker failed: {e}"
        with open(output_path, "a", encoding="utf-8") as f:
            f.write(json.dumps({"event": "error", "message": str(e)}) + "\n")
        with open(job_dir / "run.log", "a", encoding="utf-8") as lf:
            lf.write(f"[{now_iso()}] ERROR: {e}\n")

    # Update status
    duration_ms = int((time.time() - start) * 1000)
    status = json.loads((status_path).read_text(encoding="utf-8"))
    status.update({
        "status": "completed" if exit_code == 0 else "failed",
        "exit_code": exit_code,
        "duration_ms": duration_ms,
        "finished_at": now_iso(),
        **({"error": err_msg} if err_msg else {}),
    })
    write_json(status_path, status)
    return exit_code


def main():
    parser = argparse.ArgumentParser(description="Spawn Sub-Agent (detached) - auggie only")
    parser.add_argument("--worker", action="store_true", help=argparse.SUPPRESS)
    parser.add_argument("--job-id", default=None, help=argparse.SUPPRESS)
    parser.add_argument("--job-dir", default=None, help=argparse.SUPPRESS)
    parser.add_argument("--agent", default=None, help=argparse.SUPPRESS)
    parser.add_argument("agent_name", nargs="?", help="Agent name: auggie")
    parser.add_argument("prompt", nargs=argparse.REMAINDER, help="Prompt string (last arg)")
    args = parser.parse_args()

    if args.worker:
        # Worker mode
        if not args.job_id or not args.job_dir or not args.agent:
            print("Missing --job-id/--job-dir/--agent for worker mode", file=sys.stderr)
            sys.exit(2)
        sys.exit(run_worker(args.job_id, args.agent, Path(args.job_dir)))

    # Orchestrator/parent mode
    if not args.agent_name:
        print("Error: Agent name is required (auggie)", file=sys.stderr)
        sys.exit(2)

    prompt = " ".join(args.prompt).strip()
    if not prompt:
        print("Error: Prompt string is required as the final parameter (quoted).", file=sys.stderr)
        sys.exit(2)

    job_id, job_dir = init_job(args.agent_name, prompt)
    spawn_worker(job_id, args.agent_name, job_dir)

    # Print job_id and exit immediately
    print(job_id)
    sys.exit(0)


if __name__ == "__main__":
    main()

