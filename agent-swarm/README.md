# Agent Swarm - OpenHands Multi-Agent Orchestration

A CLI tool for triggering OpenHands agent swarms to execute parallel development tasks across any project.

## What Is This?

This tool enables a **primary agent to delegate tasks to multiple subordinate agents** for parallel execution:

- 🤖 **Agent Delegation** - Main agent spawns sub-agents for parallel work
- ⚡ **Parallel Execution** - Multiple agents work simultaneously
- 🎯 **Task Coordination** - Automatic result consolidation
- 🔧 **Project Agnostic** - Works with ANY codebase
- 💬 **Terminal Agent Integration** - Your main agent (Claude, Cursor) can trigger swarms

## Two Deployment Options

### Option 1: Side-by-Side (Recommended for Multiple Projects)

Keep agent-swarm as a standalone repo that works with all your projects:

```
/Users/ianbruce/code/
├── agent-swarm/              ← This repo (swarm tooling)
│   ├── swarm_cli.py
│   └── prompts/
│
├── my-web-app/               ← Your projects
├── another-project/
└── client-work/
```

**Pros:** One tool for all projects, easy updates, no duplication
**Best for:** Solo developers working on multiple projects

### Option 2: Embedded (Recommended for Team Projects)

Copy the agent-swarm files into your project:

```
/home/my-project/             ← Your project (any language)
├── app/
├── routes/
├── swarm_cli.py             ← Copied from agent-swarm
├── requirements.txt         ← Copied from agent-swarm
├── setup.sh                 ← Copied from agent-swarm
├── run.sh                   ← Copied from agent-swarm
└── .venv/                   ← Created by setup.sh (gitignored)
```

**Pros:** Self-contained, team-friendly, project-specific prompts
**Best for:** Team projects, projects with unique requirements

---

## Quick Start - Side-by-Side

```bash
# 1. Install OpenHands SDK
pip install openhands-ai

# 2. Configure API key
export GOOGLE_API_KEY="your-gemini-api-key"

# 3. Run from any project
cd ~/code/your-project
python ~/code/agent-swarm/swarm_cli.py --task "Implement user authentication"

# 4. (Optional) Add to PATH for convenience
chmod +x ~/code/agent-swarm/swarm_cli.py
echo 'export PATH="$PATH:$HOME/code/agent-swarm"' >> ~/.zshrc
```

---

## Quick Start - Embedded

```bash
# 1. Copy files to your project
cd /home/my-project
cp ~/code/agent-swarm/swarm_cli.py .
cp ~/code/agent-swarm/requirements.txt .
cp ~/code/agent-swarm/setup.sh .
cp ~/code/agent-swarm/run.sh .
chmod +x setup.sh run.sh

# 2. Run setup (creates .venv/ and installs dependencies)
./setup.sh

# 3. Configure API key
export GOOGLE_API_KEY="your-gemini-api-key"

# 4. Run
./run.sh --task "Implement user authentication"

# 5. Add to .gitignore
echo ".venv/" >> .gitignore
echo "__pycache__/" >> .gitignore
```

---

## Usage Examples

### Side-by-Side Usage

```bash
# Direct task
cd ~/code/my-project
python ~/code/agent-swarm/swarm_cli.py --task "Refactor the auth module"

# Prompt file
python ~/code/agent-swarm/swarm_cli.py --prompt ~/code/agent-swarm/prompts/example_feature.md

# Target different project
python ~/code/agent-swarm/swarm_cli.py --task "Fix bugs" --workdir ~/code/another-project

# Your main agent calls it
python ~/code/agent-swarm/swarm_cli.py \
  --task "Implement user profile with frontend, backend, and tests" \
  --workdir ~/code/my-web-app
```

### Embedded Usage

```bash
# Direct task (automatically works in project root)
cd /home/my-project
./run.sh --task "Refactor the auth module"

# Prompt file
./run.sh --prompt prompts/feature.md

# Your main agent calls it
cd /home/my-project
./run.sh --task "Implement user profile with frontend, backend, and tests"
```

---

## How It Works

### Architecture Flow

```
You (Terminal)
    ↓
Main Agent (Claude/Cursor)
    ↓ recognizes need for parallel work
    ↓ executes: swarm_cli.py --task "..."
    ↓
Swarm Orchestrator (swarm_cli.py)
    ↓ spawns sub-agents via DelegateTool
    ↓
┌───────────┬───────────┬───────────┐
│ frontend  │ backend   │    qa     │
│  agent    │  agent    │  agent    │
└─────┬─────┴─────┬─────┴─────┬─────┘
      │           │           │
      └───────────┴───────────┘
              ↓
      Shared Workspace
      (your project files)
              ↓
      Results consolidated
              ↓
Main Agent receives output
    ↓
You see the results
```

### Embedded: How Agents Know Where to Work

When using the embedded approach, the `run.sh` script automatically detects the project root:

```bash
# If run.sh is at: /home/my-project/run.sh

SCRIPT_DIR="/home/my-project"            # Where script lives
PROJECT_ROOT="$SCRIPT_DIR"               # Same directory

# Agents work in PROJECT_ROOT (the project directory)
```

---

## CLI Options

```
Required (one of):
  --task, -t TEXT       Direct task description
  --prompt, -p PATH     Path to prompt file

Optional:
  --workdir, -w PATH    Working directory (default: current dir)
  --model, -m TEXT      LLM model (default: gemini/gemini-1.5-pro-latest)
  --budget, -b FLOAT    Budget limit USD (default: 5.0)
  --verbose, -v         Show detailed output
```

---

## Example Workflow

```
You: "Hey Claude, implement the user profile feature"

Claude: "This requires parallel work on frontend, backend, and tests.
         I'll delegate to the agent swarm."

        [Executes: swarm_cli.py --task "Implement user profile..."]

Swarm Orchestrator:
  - Analyzes task
  - Spawns 3 sub-agents (frontend, backend, qa)
  - They work in parallel on your project
  - Consolidates results

Claude: "Swarm complete! Created:
         - src/components/Profile.jsx
         - src/api/profile.py
         - tests/test_profile.py
         Cost: $0.47"

You: "Great! Now run the tests."
```

---

## Configuration

### API Key Setup

```bash
# Option 1: Use existing GEMINI_API_KEY
export GOOGLE_API_KEY=$GEMINI_API_KEY

# Option 2: Set directly
export GOOGLE_API_KEY="your-key"

# Make permanent
echo 'export GOOGLE_API_KEY="your-key"' >> ~/.zshrc
source ~/.zshrc
```

### Available Models

```bash
# Default (best for complex reasoning)
--model gemini/gemini-1.5-pro-latest

# Faster, cheaper (good for simple tasks)
--model gemini/gemini-2.0-flash

# Legacy
--model gemini/gemini-pro
```

---

## Prompt Templates

Create reusable prompt files for complex tasks:

```bash
# Create a prompt file
cat > prompts/auth_feature.md << 'EOF'
# Task: Implement JWT Authentication

## Requirements
- Replace session-based auth with JWT
- Add refresh token logic
- Update all API endpoints

## Delegation Strategy
1. Backend agent: Implement JWT logic in auth.py
2. Frontend agent: Update API client to use tokens
3. QA agent: Write integration tests

## Success Criteria
- All endpoints use JWT
- Tests pass
- No breaking changes
EOF

# Use it
python swarm_cli.py --prompt prompts/auth_feature.md
```

---

## Deployment Comparison

| Feature | Side-by-Side | Embedded |
|---------|--------------|----------|
| **Setup** | Once | Per project |
| **Reusability** | ✅ All projects | ⚠️ Copy each time |
| **Updates** | ✅ Update once | ⚠️ Update each project |
| **Team Distribution** | ⚠️ Separate repo | ✅ Included in project |
| **Project Pollution** | ✅ Clean projects | ⚠️ .swarm/ in project |
| **Best For** | Solo dev, multiple projects | Team projects |

**Recommendation:** Start with **side-by-side** for simplicity. Switch to **embedded** if you need team distribution or project-specific requirements.

---

## Troubleshooting

### "OpenHands SDK not installed"
```bash
pip install openhands-ai
```

### "GOOGLE_API_KEY not set"
```bash
export GOOGLE_API_KEY="your-key"
```

### "Working directory does not exist"
```bash
# Use absolute path or navigate first
cd ~/code/your-project
swarm_cli.py --task "..."
```

### "Virtual environment not found" (Embedded only)
```bash
./setup.sh
```

### "Permission denied" (Embedded only)
```bash
chmod +x setup.sh run.sh
```

---

## Requirements

- Python 3.10+
- OpenHands SDK (`pip install openhands-ai`)
- Google Gemini API key

---

## Files in This Repo

```
agent-swarm/
├── swarm_cli.py              ← Main CLI script
├── requirements.txt          ← Python dependencies
├── setup.sh                  ← Setup script (creates .venv/)
├── run.sh                    ← Run wrapper (activates .venv)
├── prompts/                  ← Example prompt templates
│   └── example_feature.md
├── .env                      ← Your API keys (gitignored)
├── .gitignore                ← Git exclusions
├── README.md                 ← This file (complete guide)
├── QUICK_START.md            ← Quick reference
└── EMBEDDED_GUIDE.md         ← Embedded deployment details
```

---

## License

MIT

## Credits

Built on [OpenHands](https://github.com/OpenHands/software-agent-sdk) - Open-source AI software development agents.

