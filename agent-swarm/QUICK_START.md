# Quick Start Guide

## Choose Your Deployment

### Option 1: Side-by-Side (Recommended)
**Best for:** Solo developers, multiple projects, easy updates

```bash
# 1. Install
pip install openhands-ai

# 2. Configure
export GOOGLE_API_KEY="your-gemini-api-key"

# 3. Use from any project
cd ~/code/your-project
python ~/code/agent-swarm/swarm_cli.py --task "Your task here"
```

---

### Option 2: Embedded
**Best for:** Team projects, self-contained setup

```bash
# 1. Copy files to your project
cd /home/my-project
cp ~/code/agent-swarm/swarm_cli.py .
cp ~/code/agent-swarm/requirements.txt .
cp ~/code/agent-swarm/setup.sh .
cp ~/code/agent-swarm/run.sh .
chmod +x setup.sh run.sh

# 2. Setup
./setup.sh

# 3. Configure
export GOOGLE_API_KEY="your-gemini-api-key"

# 4. Use
./run.sh --task "Your task here"

# 5. Gitignore
echo ".venv/" >> .gitignore
echo "__pycache__/" >> .gitignore
```

---

## Common Commands

### Side-by-Side

```bash
# Direct task
python ~/code/agent-swarm/swarm_cli.py --task "Implement feature X"

# Prompt file
python ~/code/agent-swarm/swarm_cli.py --prompt prompts/feature.md

# Different project
python ~/code/agent-swarm/swarm_cli.py --task "Fix bugs" --workdir ~/code/other-project
```

### Embedded

```bash
# Direct task (auto-detects project root)
./run.sh --task "Implement feature X"

# Prompt file
./run.sh --prompt prompts/feature.md
```

---

## How Your Main Agent Uses It

Your terminal agent (Claude, Cursor, etc.) recognizes when a task needs parallel work and executes:

```bash
# Side-by-side
python ~/code/agent-swarm/swarm_cli.py \
  --task "Implement user profile with frontend, backend, and tests" \
  --workdir ~/code/my-project

# Embedded
cd /home/my-project
./run.sh --task "Implement user profile with frontend, backend, and tests"
```

---

## Troubleshooting

```bash
# OpenHands not installed
pip install openhands-ai

# API key not set
export GOOGLE_API_KEY="your-key"

# Embedded: venv not found
./setup.sh

# Embedded: permission denied
chmod +x setup.sh run.sh
```

---

## Full Documentation

See [README.md](README.md) for complete documentation.

