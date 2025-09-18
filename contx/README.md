# Universal Context Engineering Framework

> **Transform your AI-assisted development with structured, consistent context across all your tools**

A comprehensive, tool-agnostic context engineering system that provides structured guidance for AI-assisted development. Works seamlessly with Augment, Warp, Gemini CLI, and other AI coding tools.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![GitHub stars](https://img.shields.io/github/stars/ianbrucey/contx.svg)](https://github.com/ianbrucey/contx/stargazers)
[![GitHub issues](https://img.shields.io/github/issues/ianbrucey/contx.svg)](https://github.com/ianbrucey/contx/issues)

## 🎯 Why Context Engineering?

AI coding assistants are powerful, but they often lack project-specific context, leading to:
- ❌ Inconsistent code patterns across team members
- ❌ Repeated explanations of project architecture
- ❌ Generic solutions that don't fit your specific needs
- ❌ Time wasted on back-and-forth clarifications

**This framework solves these problems by providing:**
- ✅ **Structured Context**: Hierarchical project knowledge that scales
- ✅ **Tool Agnostic**: Works with Augment, Warp, Gemini CLI, and more
- ✅ **Team Consistency**: Shared context ensures uniform AI guidance
- ✅ **Template-Driven**: Proven patterns for different task types
- ✅ **Maintainable**: Easy to update and evolve with your project

## 🚀 Quick Start (5 minutes)

### 1. Install to Your Project

```bash
# Clone to your project as a submodule (recommended)
cd /path/to/your/project
git submodule add https://github.com/ianbrucey/contx.git .contx

# Or clone directly
git clone https://github.com/ianbrucey/contx.git .contx
```

### 2. Set Up Context Engine

```bash
cd .contx && ./scripts/sync-all.sh
```

**NOTE**: The `sync-all.sh` script is currently under development and may require manual intervention or debugging. Its purpose is to automate the setup of the `context-engine` and agent-specific configuration files.

This command will:
- Create a `context-engine/` directory in your project root (outside the submodule).
- Copy `global-context.md`, `domain-contexts/`, and `templates/` into `context-engine/`.
- Generate agent-specific configuration files (`.augment/rules/`, `GEMINI.md`, `WARP.md`) in your project root.
- Add instructions within these generated files to direct agents to the `context-engine/` for context.

### 3. Customize & Test

```bash
# Customize for your project
edit ./context-engine/global-context.md

# Test with your AI tool
# Augment: "Implement user auth. Use @task-template"
# Warp: Ask any development question in terminal
# Gemini: Run 'gemini' and describe your task
```

## 📁 Framework Architecture

```
.contx/                                # Universal Context Framework (submodule)
├── 📋 global-context.md              # Source for global context
├── 📝 templates/                     # Source for task templates
│   ├── 01-problem-definition.md
│   ├── 02-research.md
│   ├── 03-plan.md
│   └── 04-implementation.md
├── 🏗️ domain-contexts/              # Source for domain-specific knowledge
│   ├── auth-context.md
│   ├── database-context.md
│   ├── api-context.md
│   └── [your-domain].md
├── 🔄 scripts/                      # Tool synchronization scripts
│   └── sync-all.sh                  # Syncs context to all tools
└── 📖 SETUP_GUIDE.md               # Detailed setup instructions

./context-engine/                      # Centralized context for agents (outside submodule)
├── 📋 global-context.md              # Project overview & architecture
├── 📝 templates/                     # Task execution templates
│   ├── 01-problem-definition.md
│   ├── 02-research.md
│   ├── 03-plan.md
│   └── 04-implementation.md
└── 🏗️ domain-contexts/              # Domain-specific knowledge
    ├── auth-context.md
    ├── database-context.md
    ├── api-context.md
    └── [your-domain].md
```

### 🔄 How It Works

The core idea is to create a single source of truth for project context and then distribute it in a way that various AI tools can understand and use.

1.  **Single Source of Truth (`.contx/`)**: The process starts with the `.contx` submodule, which acts as the master template for all project context. It contains the foundational templates for global context, domain-specific knowledge, and task structures.

2.  **Synchronization (`sync-all.sh`)**: A key script, `contx/scripts/sync-all.sh`, automates the main setup. When executed, it:
    *   **Creates `context-engine/`**: A directory in your project root that will hold the live, project-specific context.
    *   **Populates Context**: It copies the templates from `.contx` into the `context-engine` directory.
    *   **Generates Pointers**: It creates tool-specific configuration files (e.g., `GEMINI.md`, `WARP.md`) in the root directory. These files act as pointers, instructing AI tools to load their context from the `context-engine`.

3.  **Customization (`context-engine/`)**: After the initial sync, the files within the `context-engine` directory are meant to be customized. You would edit `context-engine/global-context.md`, for example, to fill in the specific details of your project, transforming it from a generic template into actionable guidance.

4.  **Hierarchical Context & Usage**: Once set up, AI tools automatically load the structured context from `context-engine`. This context is hierarchical (Global → Domain → Task), allowing for a comprehensive understanding of the project at different levels of detail.

## 🛠️ Supported AI Tools

|| Tool | Type | Usage |
||------|------|-------|
|| **🔧 Augment** | IDE Extension | `Use @task-template` |
|| **💻 Warp** | Terminal AI | Auto-applies based on directory |
|| **🤖 Gemini CLI** | Command Line | Auto-loads context on startup |
|| **🌐 Universal** | All Tools | Sync to all tools at once |

### 🔧 Augment Integration

**Perfect for**: VSCode and JetBrains users who want structured templates and auto-triggered context

**Usage examples**:
`Implement user authentication. Use the task templates to guide you.`

**Features**:
- ✅ Global context always loaded
- ✅ Manual templates via `@template-name`
- ✅ Auto-triggered domain contexts
- ✅ Task management integration

### 💻 Warp Integration

**Perfect for**: Terminal-focused developers who want contextual AI assistance

**Usage**:
Just ask questions in Warp - context applies automatically
`How should I structure this API endpoint?`
`What's the best way to handle database migrations?`

**Features**:
- ✅ Automatic context loading
- ✅ Directory-specific rules
- ✅ Hierarchical context inheritance
- ✅ No manual template selection needed

### 🤖 Gemini CLI Integration

**Perfect for**: Command-line workflows with persistent context memory

**Usage**:
gemini
`Help me implement user authentication`
`What testing strategy should I use?`

**Features**:
- ✅ Automatic context loading
- ✅ Conversation memory
- ✅ Hierarchical context files
- ✅ MCP integration support

## 📝 Customization Guide

### 🎯 1. Global Context Setup

The heart of your context system - defines your project's DNA:

```bash
edit ./context-engine/global-context.md
```

**Essential sections to customize**:
```markdown
## Problem Definition & Scope
**Problem Statement**: [Your specific problem]
**Target Users**: [Who uses your system]
**Success Metrics**: [How you measure success]

## Solution Architecture
**Technology Stack**:
- Frontend: [React, Vue, Angular, etc.]
- Backend: [Node.js, Python, Java, etc.]
- Database: [PostgreSQL, MongoDB, etc.]

## Key Constraints & Guidelines
**Coding Standards**: [ESLint, Prettier, etc.]
**Performance Requirements**: [Response times, etc.]
```

### 🏗️ 2. Domain Context Creation

Add specialized knowledge for your domains by creating new files in `./context-engine/domain-contexts/`.

**Popular domain contexts**:
- 🔐 Authentication & Authorization
- 💾 Database Operations & Migrations
- 🌐 API Design & Implementation
- ⚛️ Frontend Components & State
- 📧 Email & Notifications
- 💳 Payment Processing
- 📊 Analytics & Reporting

### 📋 3. Template Customization

Adapt templates to your team's workflow by editing the files in the `./context-engine/templates/` directory. The new four-phase structure is designed to be modular and flexible.

- `01-problem-definition.md`: Define the "what" and "why".
- `02-research.md`: Explore potential solutions.
- `03-plan.md`: Create a detailed technical plan.
- `04-implementation.md`: Track progress and log decisions.

## 🔄 Development Workflow

The new task workflow is divided into four distinct phases, each with its own template file. This structure provides a clear separation of concerns and better progress tracking.

- **Phase 1: Problem Definition** (`01-problem-definition.md`)
- **Phase 2: Research** (`02-research.md`)
- **Phase 3: Plan** (`03-plan.md`)
- **Phase 4: Implementation** (`04-implementation.md`)

### 🚀 Before Each Task

Tasks are represented by folders using a clear naming convention so agents can reliably discover and load scoped context.

Recommended naming patterns:
- Preferred (category + sequence + slug): `task-<CATEGORY>-<NNN>-<short-slug>`
  - Example: `task-DOC-001-document-upload-feature`
  - CATEGORY: 2–6 uppercase letters (e.g., DOC, FEAT, BUG, OPS, TEST)
  - NNN: zero-padded sequence (001, 002, ...)
  - short-slug: lowercase kebab-case summary
- Simple (legacy): `task-<id>-<short-slug>`
  - Example: `task-123-document-upload`

Where to create tasks:
- Agent-agnostic (recommended): `./context-engine/tasks/`
- Historical examples: `./.augment/tasks/` (see these for real examples already in this repo)
  - Examples present today:
    - `task-DOC-001-document-upload-feature/`
    - `task-TEST-001-context-consolidation-test/`

Task folder contents (minimum):
- `01-problem-definition.md`
- `02-research.md`
- `03-plan.md`
- `04-implementation.md`
- Optional: `README.md`, `progress.md`, `decisions.md`, `next-steps.md`, `code-links.md`, `task-metadata.json`

How to initiate a new task:
1. 📊 Create a new task directory under `./context-engine/tasks/` using the naming convention above
2. 📝 Start by filling out `01-problem-definition.md` (use the templates as a guide)
3. 🔄 If you’ve updated `contx/` (the source), run a sync so all agent views are up to date:
   ```bash
   cd .contx && ./scripts/sync-all.sh
   ```
4. ▶️ Proceed to `02-research.md`, `03-plan.md`, then `04-implementation.md` as work advances

#### Active task pointer (optional but recommended)
- To indicate the current focus, write a JSON pointer at `context-engine/active-task.json`.
- Use the helper script to set it:
  ```bash
  ./contx/scripts/set-active-task.sh tasks/task-CAT-001-my-task --title "My Task" --status in-progress --priority P1
  ```
- Agents should check this file first when building context.

#### Task metadata schema (optional)
Each task may include a `task-metadata.json` file to help agents track state, priority, and cross-links. A shared JSON Schema is provided at:

- `./context-engine/task-metadata.schema.json`

Reference it from your task file using `$schema` and fill out the fields that are relevant:

```json
{
  "$schema": "../task-metadata.schema.json",
  "id": "DOC-001",
  "slug": "document-upload-feature",
  "title": "Document upload pipeline (Gemini-powered)",
  "status": "scoping",
  "priority": "P1",
  "owner": "you@example.com",
  "labels": ["docs", "backend"],
  "links": [
    { "type": "doc", "path": "01-problem-definition.md" },
    { "type": "doc", "path": "03-plan.md" }
  ]
}
```

### 📅 Maintenance Schedule

| Frequency | Duration | Tasks |
|-----------|----------|-------|
| **📅 Weekly** | 5 min | Update domain contexts with new patterns |
| **🏗️ After major features** | 15 min | Document architectural decisions |
| **📊 Monthly** | 30 min | Review effectiveness, refine templates |

### 🔄 Sync Workflow

```bash
# After updating any .contx/ files
cd .contx

# Sync all tools
./scripts/sync-all.sh          # Everything
```

## 🚀 Advanced Features

### 🔄 Git Integration

Automate context synchronization with git hooks:

```bash
# Install git hooks for automatic sync
echo '#!/bin/bash' > .git/hooks/post-commit
echo 'cd .contx && ./scripts/sync-all.sh' >> .git/hooks/post-commit
chmod +x .git/hooks/post-commit

# Now context syncs automatically after each commit
```

### 👥 Team Collaboration

**Version Control Setup**:
```bash
# Add framework to version control
git add .contx/
git add .augment/ WARP.md GEMINI.md  # Generated files
git commit -m "Add universal context engineering framework"

# Team members can now sync with:
cd .contx && ./scripts/sync-all.sh
```

**Team Onboarding Script**:
```bash
# Create setup script for new team members
cat > setup-context.sh << 'EOF'
#!/bin/bash
echo "🚀 Setting up context engineering..."
cd .contx && ./scripts/sync-all.sh
echo "✅ Ready! Choose your AI tool and start coding."
EOF
chmod +x setup-context.sh
```

### 🔍 Cross-Tool Compatibility

The framework maintains consistency by:
- 🔄 **Format Conversion**: Adapts to each tool's specific requirements
- 🎯 **Semantic Preservation**: Maintains meaning across platforms
- ⚡ **Tool Optimization**: Leverages each tool's unique features
- 🔗 **Single Source**: One context, multiple outputs

