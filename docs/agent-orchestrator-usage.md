# Agent Orchestrator: Usage Guide for a Main Agent

This guide explains how a primary ("main") agent can delegate sub-tasks to the `gemini` and `auggie` CLI agents using the orchestrator script, manage them in parallel with a concurrency cap, and collect results.

Script: `scripts/agent_orchestrator.py`

## What it does
- Runs many gemini/auggie jobs concurrently (configurable `--concurrency`).
- Applies per-task timeouts and retries.
- Stores each job's stdout/stderr in per-job files and writes metadata to JSON.
- Produces a run ledger and a summary for auditability.
- Aggregates outputs into a single JSON file for easy ingestion by your workflow.

## Requirements
- `gemini` and/or `auggie` CLIs installed and available on PATH
- Python 3.8+

## Quick start
1) Create a tasks file (JSON array). Example `tasks.json`:

```json
[
  {"agent": "gemini", "prompt": "Return the string 'ALPHA' only.", "tag": "G-ALPHA"},
  {"agent": "gemini", "prompt": "Return the string 'BETA' only.",  "tag": "G-BETA"},
  {"agent": "auggie", "prompt": "Return the string 'ALPHA' only.", "tag": "A-ALPHA"},
  {"agent": "auggie", "prompt": "Return the string 'BETA' only.",  "tag": "A-BETA"}
]
```

2) Dispatch the batch (runs concurrently with a cap of 4, 2-minute timeout):

```bash
python scripts/agent_orchestrator.py run \
  --tasks tasks.json \
  --concurrency 4 \
  --timeout 120 \
  --retries 0 \
  --run-dir .agent-runs
```

- The command prints the path of the new run directory, e.g. `.agent-runs/2025-09-18_12-34-56`.
- Inside that run directory:
  - `tasks.json` – the input tasks
  - `run_ledger.jsonl` – one JSON line per job completion
  - `summary.json` – aggregate counts by status
  - `jobs/` – per-job outputs and metadata
    - `jobs/<tag>.out` – agent stdout/stderr
    - `jobs/<tag>.json` – metadata (status, start/end times, duration, exit code)

3) Collect (aggregate outputs into one file):

```bash
python scripts/agent_orchestrator.py collect \
  --run-dir .agent-runs/2025-09-18_12-34-56
```

- Prints the path to `combined.json` with an array of `{tag, output_path, output}` entries.

## How a main agent should use this

- Delegation:
  - Split work into atomic prompts (script generation, test stubs, config drafts, doc outlines).
  - Write them to a `tasks.json` with meaningful `tag`s.
- Parallelization strategy:
  - Set `--concurrency` based on how much you can run at once without rate-limiting (start with 2–4).
  - Use `--timeout` to prevent long stalls; failed/timeout jobs can be retried with `--retries`.
- Integration loop:
  1) Run the batch and note the run directory path printed to stdout.
  2) Continue your own coding/testing while jobs run.
  3) When done, run `collect` to get `combined.json`.
  4) Parse `combined.json`, validate outputs, and integrate artifacts into the repo or your pipeline.
- Traceability & Audit:
  - `run_ledger.jsonl` and per-job JSON files provide a durable record.
  - You can correlate `tag`s with tickets or sub-task IDs.

## Tasks format
Each task is an object with:
- `agent` (required): `"gemini"` or `"auggie"`
- `prompt` (required): string passed to the agent with `-p`
- `tag` (optional): custom identifier; if omitted, a unique one is generated
- `timeout` (optional): per-task override in seconds (default comes from `--timeout`)
- `retries` (optional): per-task override (default comes from `--retries`)

Example with per-task overrides:

```json
[
  {"agent": "gemini", "prompt": "Generate a bash script that echoes HELLO", "tag": "G-SCRIPT", "timeout": 60},
  {"agent": "auggie", "prompt": "Draft a README section about setup", "tag": "A-README", "retries": 1}
]
```

## Tips for robust usage
- Prefer deterministic prompts (e.g., ask the agent to output plain text or JSON only).
- If you want structured results, instruct the agent to produce a single JSON object; then parse the `jobs/*.out` files.
- Start with low concurrency to observe rate limits and scale up as needed.
- Use distinctive `tag`s to make outputs easy to locate.

## Known limitations
- The orchestrator assumes `gemini` and `auggie` accept `-p "<prompt>"` and write to stdout.
- Cancellation of in-flight jobs is not exposed as a command; to stop a batch, interrupt the `run` command.

## File layout recap
```
.agent-runs/
  <timestamp>/
    tasks.json
    run_ledger.jsonl
    summary.json
    jobs/
      <tag>.out
      <tag>.json
```

## Next steps
If helpful, we can extend the orchestrator to:
- Accept prompts from stdin or CSV
- Add a `status` subcommand and live progress bar
- Enforce output JSON schemas and validation
- Push artifacts directly into repo locations

