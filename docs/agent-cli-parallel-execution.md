# Parallel Execution of gemini and auggie CLI Agents

This document records empirical findings and recommended patterns for running the `gemini` and `auggie` CLI agents in parallel from within the Augment workflow.

## Summary of Findings
- Asynchronous launching works: both agents can be dispatched in non-blocking mode while other tasks continue.
- Terminal manager behavior: directly launching short-lived processes with `wait=false` often results in the terminal being marked `killed (-1)` even though the subprocess completed successfully. Detaching with `nohup … &` avoids this and reliably captures output.
- Effective concurrency: At least two terminals show as concurrently running. Higher practical concurrency is achievable by detaching processes and writing outputs to files.
- Recommended pattern: Use `nohup` + backgrounding + file-based logging to run many concurrent agent jobs and collect results later without blocking editing, tests, or other tooling.

## Environment & Constraints
- Platform: macOS (bash shell), running via Augment terminal manager.
- Direct `wait=false` runs may end the parent terminal session quickly; short-lived agent invocations can appear as "killed" even if they emitted correct output. Detached/bg runs mitigate this.

## 1) Asynchronous Execution
- Yes: Launch gemini/auggie without blocking and continue coding, editing, or running tests.
- Robust approach (detached):

```bash
nohup gemini -p "Your prompt A" >/tmp/gem_A.out 2>&1 & sleep 5
nohup auggie -p  "Your prompt B" >/tmp/aug_B.out 2>&1 & sleep 5
# Continue with other tasks, then later:
cat /tmp/gem_A.out
cat /tmp/aug_B.out
```

Why this works:
- `nohup` detaches the agent from the controlling terminal, keeping it alive after the parent shell returns.
- Redirecting stdout/stderr to a file allows reliable, later retrieval.
- A small `sleep` keeps the spawning shell open momentarily, preventing premature teardown.

## 2) Concurrent Process Limits (Observed)
- At least two terminals can be concurrently `running`.
- Additional `wait=false` terminals often flip to `killed (-1)` rapidly; however, the agent process may still have completed successfully.
- Using detached/background runs with file logging enables running many concurrent jobs in practice; the limiting factor becomes system resources and coordination, not the terminal manager.

## 3) Practical Workflow Integration
- Delegation model: Offload sub-tasks (script generation, test drafts, data scaffolding, planning) to gemini/auggie while you:
  - Edit code or dbt models
  - Run unit tests/linters/builds
  - Review PR diffs or context docs
- Integration steps:
  1) Dispatch multiple agents with unique tags and output files
  2) Continue with your work
  3) Periodically collect outputs, validate, and integrate into the repo

Suggested conventions:
- Output path pattern: `/tmp/{agent}_{tag}.out` during experiments; for persistent artifacts, prefer `./.agent-runs/{timestamp}/{agent}_{tag}.out` within the repo (gitignored if desired).
- Include a clear header or JSON payload in the agent's response for structured parsing.

## 4) Process Management & Monitoring
- Listing and status (foreground terminals):
  - Use the Augment terminal manager to list processes; terminals may show as `running`, `completed`, or `killed`.
- Collecting results:
  - Foreground: read from the terminal buffer if the job is long-lived
  - Detached/background: `cat` the output files (`/tmp/*.out`) or `tail -f` for live monitoring
- Coordination strategy:
  - Assign unique IDs (e.g., `YYYYMMDD-HHMMSS-<task>-<N>`)
  - Use a small registry (CSV/JSON) mapping ID → agent, prompt, output path, start time
  - Implement timeouts and retries for robustness

### Minimal Bash Orchestration Pattern
```bash
run_agent() {
  agent="$1"   # gemini | auggie
  tag="$2"     # unique label
  prompt="$3"  # quoted prompt
  out="/tmp/${agent}_${tag}.out"
  nohup "$agent" -p "$prompt" >"$out" 2>&1 & sleep 3
  echo "$agent,$tag,$out"  # caller can record this
}

# Example dispatch
run_agent gemini GEM-ALPHA "Generate a shell script that prints ALPHA"
run_agent gemini GEM-BETA  "Generate a shell script that prints BETA"
run_agent auggie AUG-ALPHA "Write a README header titled AUG-ALPHA"
run_agent auggie AUG-BETA  "Write a README header titled AUG-BETA"

# Later, collect
for f in /tmp/{gemini,auggie}_*; do
  [ -f "$f" ] && echo "--- $f ---" && cat "$f" && echo
done
```

## Evidence from Test Runs
Detached runs produced the following representative outputs (prefix lines omitted for brevity):
- `/tmp/gem_alpha.out` → `Loaded cached credentials.` + `GEMINI-ALPHA`
- `/tmp/gem_beta.out`  → `Loaded cached credentials.` + `GEMINI-BETA`
- `/tmp/aug_alpha.out` → `🤖` + `AUGGIE-ALPHA`
- `/tmp/aug_beta.out`  → `🤖` + `AUGGIE-BETA`
- Additional set confirmed similarly: `GEMINI-C1`, `GEMINI-C2`, `AUGGIE-C1`, `AUGGIE-C2`

## Known Limitations & Tips
- Short-lived commands with `wait=false` may show as `killed` in the terminal list even if successful; prefer detached runs for reliability.
- Always write to output files and tag runs for traceability.
- If you need strict guarantees, wrap prompts to produce JSON and validate schema on collection.
- Be mindful of rate limits, credentials, and environment setup for each agent.

## Future Enhancements
- Add a small repo-local orchestrator (Python/Node) that:
  - Spawns N parallel jobs with a concurrency cap
  - Persists a run ledger (JSONL) with metadata
  - Implements timeouts, retries, and structured output parsing
  - Provides a `collect` command to aggregate results into a single report

## Quick Reference
- Non-blocking (detached) launch:
  - `nohup {agent} -p "<prompt>" > /tmp/{agent}_{tag}.out 2>&1 & sleep 5`
- Collect results:
  - `cat /tmp/{agent}_{tag}.out`
- Live monitor:
  - `tail -f /tmp/{agent}_{tag}.out`


