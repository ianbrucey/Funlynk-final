# Agent Swarm - Embedded Deployment Guide

This guide explains how to embed agent swarm tooling directly in your project.

## Quick Start

```bash
# 1. Copy files to your project
cd /home/my-project
cp ~/code/agent-swarm/swarm_cli.py .
cp ~/code/agent-swarm/requirements.txt .
cp ~/code/agent-swarm/setup.sh .
cp ~/code/agent-swarm/run.sh .
chmod +x setup.sh run.sh

# 2. Setup (first time only)
./setup.sh

# 3. Configure API key
export GOOGLE_API_KEY="your-gemini-api-key"

# 4. Run
./run.sh --task "Implement feature X"

# 5. Add to .gitignore
echo ".venv/" >> .gitignore
echo "__pycache__/" >> .gitignore
```

## Files You Need

```
/home/my-project/
├── app/                     ← Your project files
├── routes/
├── swarm_cli.py            ← Copied from agent-swarm
├── requirements.txt        ← Copied from agent-swarm
├── setup.sh                ← Copied from agent-swarm
├── run.sh                  ← Copied from agent-swarm
├── .venv/                  ← Created by setup.sh (gitignored)
└── prompts/                ← Optional: project-specific prompts
    └── feature.md
```

## Usage

### Direct Task

```bash
./run.sh --task "Refactor the authentication module"
```

### Prompt File

```bash
./run.sh --prompt prompts/feature.md
```

### From Your Main Agent

Your terminal agent (Claude, Cursor) would execute:

```bash
cd /home/my-project
./run.sh --task "Implement user profile feature"
```

## Setup Details

### What `setup.sh` Does:

1. Creates Python virtual environment in `.venv/`
2. Installs OpenHands SDK and dependencies
3. Keeps dependencies isolated from system Python

### What `run.sh` Does:

1. Activates the virtual environment
2. Runs `swarm_cli.py` with project root as default `--workdir`
3. Passes all arguments through

## Environment Variables

```bash
# Required
export GOOGLE_API_KEY="your-gemini-api-key"

# Optional
export LLM_MODEL="gemini/gemini-1.5-pro-latest"  # Default model
```

## Gitignore

Add to your `.gitignore`:

```
# Agent Swarm
.venv/
__pycache__/
```

## CLI Options

```bash
./run.sh [OPTIONS]

Required (one of):
  --task, -t TEXT       Direct task description
  --prompt, -p PATH     Path to prompt file

Optional:
  --workdir, -w PATH    Working directory (default: project root)
  --model, -m TEXT      LLM model (default: gemini/gemini-1.5-pro-latest)
  --budget, -b FLOAT    Budget limit USD (default: 5.0)
  --verbose, -v         Show detailed output
```

## Example: Laravel Project

```
/home/my-project/                    ← Laravel project
├── app/
├── routes/
├── composer.json
├── swarm_cli.py                     ← Swarm tooling
├── requirements.txt
├── setup.sh
├── run.sh
├── .venv/                           ← Isolated Python deps (gitignored)
└── .gitignore                       ← Add .venv/

# Usage:
cd /home/my-project
./run.sh --task "Add API endpoint for user profiles"
```

## Updating

To update OpenHands SDK:

```bash
.venv/bin/pip install --upgrade openhands-ai
```

## Troubleshooting

**"Virtual environment not found"**
```bash
./setup.sh
```

**"GOOGLE_API_KEY not set"**
```bash
export GOOGLE_API_KEY="your-key"
```

**"Permission denied"**
```bash
chmod +x setup.sh run.sh
```

## Why Embedded?

- ✅ Self-contained - Each project has its own swarm setup
- ✅ Project-specific prompts live with the project
- ✅ No external dependencies - Everything in project root
- ✅ Team-friendly - Clone and run `./setup.sh`

## Alternative: Side-by-Side

If you prefer one swarm tool for all projects, see the main repo's `README.md` for the side-by-side approach.

