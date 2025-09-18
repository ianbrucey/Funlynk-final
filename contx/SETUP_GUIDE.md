# Universal Context Engineering Setup Guide

## Overview

This guide will help you set up the Universal Context Engineering Framework for your project, enabling consistent AI assistance across Augment, Warp, and Gemini CLI.

## Prerequisites

- Git repository for your project
- At least one of the supported AI tools:
  - **Augment**: VSCode or JetBrains extension
  - **Warp**: Terminal with AI features
  - **Gemini CLI**: Command-line AI assistant

## Quick Setup (5 minutes)

### 1. Clone the Framework

```bash
# Clone to your project directory
cd /path/to/your/project
git clone https://github.com/ianbrucey/contx.git .contx

# Or download and extract if you prefer
curl -L https://github.com/ianbrucey/contx/archive/main.zip -o contx.zip
unzip contx.zip
mv contx-main .contx
```

### 2. Set Up Context Engine

```bash
cd .contx
./scripts/sync-all.sh
```

**NOTE**: The `sync-all.sh` script is currently under development and may require manual intervention or debugging. Its purpose is to automate the setup of the `context-engine` and agent-specific configuration files.

This command will:
- Create a `context-engine/` directory in your project root (outside the submodule).
- Copy `global-context.md`, `domain-contexts/`, and `templates/` into `context-engine/`.
- Generate agent-specific configuration files (`.augment/rules/`, `GEMINI.md`, `WARP.md`) in your project root.
- Add instructions within these generated files to direct agents to the `context-engine/` for context.

### 3. Customize Global Context

```bash
# Edit the global context for your project
edit ./context-engine/global-context.md
```

Fill in your project-specific information:
- Problem statement and scope
- Technology stack
- Core modules and responsibilities
- Acceptance criteria
- Coding standards

### 4. Test the Setup

**Augment Test:**
```
"Implement user login feature. Use the task templates to guide you."
```

**Warp Test:**
- Open Warp in your project directory
- Ask: "How should I structure a new API endpoint?"

**Gemini CLI Test:**
```bash
gemini
> Help me implement user authentication
```

## Detailed Customization

### Global Context Configuration

Edit `.contx/global-context.md` with your project details:

```markdown
## Problem Definition & Scope
**Problem Statement**: [Your specific problem]
**Project Scope**: [What's included/excluded]
**Target Users**: [Who will use this]
**Success Metrics**: [How you measure success]

## Solution Architecture
**Architecture Pattern**: [MVC, microservices, etc.]
**Technology Stack**:
- Frontend: [Your frontend tech]
- Backend: [Your backend tech]
- Database: [Your database choice]
- Infrastructure: [Your deployment strategy]
```

### Domain Context Creation

Create domain-specific contexts for your main areas:

```bash
# Copy and customize existing contexts
cp .contx/domain-contexts/auth-context.md .contx/domain-contexts/my-auth-context.md

# Or create new ones
touch .contx/domain-contexts/payment-context.md
touch .contx/domain-contexts/notification-context.md
```

### Template Customization

Modify templates to match your workflow by editing the files in the `.contx/templates/` directory. The new four-phase structure is designed to be modular and flexible.

- `01-problem-definition.md`: Define the "what" and "why".
- `02-research.md`: Explore potential solutions.
- `03-plan.md`: Create a detailed technical plan.
- `04-implementation.md`: Track progress and log decisions.

### Task Directory Structure

When creating a new task, the directory structure should follow this convention: `task-[task-id]-[task-short-name]`. For example: `task-001-draft-workspace-skeleton`.

## Tool-Specific Configuration

### Augment Configuration

1. **Verify Rules Loading:**
   - Open Augment
   - Check that rules appear in the rules panel
   - Global context should be marked as "Always"
   - Templates should be marked as "Manual"

2. **Add User Guidelines:**
   - Go to Augment Settings > User Guidelines
   - Add the workflow instructions from the sync output

3. **Test Auto Rules:**
   - Start a task with authentication keywords
   - Verify auth context auto-triggers

### Warp Configuration

1. **Verify WARP.md Files:**
   - Check `WARP.md` exists in project root
   - Verify subdirectory-specific files are created
   - Test navigation between directories

2. **Test Context Loading:**
   - Open Warp in your project directory
   - Ask: "How should I structure a new API endpoint?"
   - Verify project context is applied

### Gemini CLI Configuration

1. **Verify GEMINI.md Files:**
   - Check `GEMINI.md` exists in project root
   - Verify hierarchical context structure
   - Test subdirectory context inheritance

2. **Test Context Loading:**
   - Run `gemini` in your project directory
   - Verify context is automatically loaded
   - Test memory management across sessions

## Advanced Setup

### Git Integration

Automate context synchronization with git hooks:

```bash
# Install git hooks
# The sync-all.sh script handles the setup of agent-specific files.
echo '#!/bin/bash' > .git/hooks/post-commit
echo 'cd .contx && ./scripts/sync-all.sh' >> .git/hooks/post-commit
chmod +x .git/hooks/post-commit
```

### Team Collaboration

1. **Version Control Setup:**
   ```bash
   # Add to version control
   git add .contx/
   git add .augment/ WARP.md GEMINI.md  # Generated files
   git commit -m "Add universal context engineering framework"
   ```

2. **Team Onboarding:**
   ```bash
   # Create team setup script
   cat > setup-team-context.sh << 'EOF'
   #!/bin/bash
   echo "Setting up context engineering for team member..."
   cd .contx
   ./scripts/sync-all.sh
   echo "Setup complete! Choose your preferred AI tool and start coding."
   EOF
   chmod +x setup-team-context.sh
   ```

### Continuous Integration

Add context validation to CI:

```yaml
# .github/workflows/context-validation.yml
name: Context Validation
on: [push, pull_request]
jobs:
  validate-context:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Validate Context Files
        run: |
          # Check required files exist
          test -f ./context-engine/global-context.md
          test -f ./context-engine/templates/01-problem-definition.md
          test -f ./context-engine/templates/02-research.md
          test -f ./context-engine/templates/03-plan.md
          test -f ./context-engine/templates/04-implementation.md
          
          # Validate sync scripts work
          cd .contx
          ./scripts/sync-all.sh
```

## Maintenance

### Weekly Tasks (5 minutes)

1. **Update Domain Contexts:**
   ```bash
   # Review and update patterns discovered during development
   edit ./context-engine/domain-contexts/auth-context.md
   ```

2. **Sync Changes:**
   ```bash
   cd .contx
   ./scripts/sync-all.sh
   ```

### After Major Features (15 minutes)

1. **Document Decisions:**
   ```bash
   # Update global context with architectural decisions
   edit ./context-engine/global-context.md
   ```

2. **Refine Templates:**
   ```bash
   # Improve templates based on what worked/didn't work
   edit ./context-engine/templates/01-problem-definition.md
   edit ./context-engine/templates/02-research.md
   edit ./context-engine/templates/03-plan.md
   edit ./context-engine/templates/04-implementation.md
   ```

### Monthly Review (30 minutes)

1. **Assess Effectiveness:**
   - Review AI assistant performance
   - Identify context gaps
   - Gather team feedback

2. **System Improvements:**
   - Add new domain contexts
   - Create specialized templates
   - Update auto-trigger descriptions

## Troubleshooting

### Common Issues

**Issue**: Auto rules not triggering in Augment
**Solution**: Check the `description` field in rule frontmatter matches task keywords

**Issue**: Warp not loading project context
**Solution**: Verify `WARP.md` exists in project root and current directory

**Issue**: Gemini CLI not finding context
**Solution**: Check `GEMINI.md` exists and run `gemini` from project directory

**Issue**: Templates too verbose for team
**Solution**: Customize templates in `./context-engine/templates/` to match team preferences

**Issue**: Context getting stale
**Solution**: Set up regular review schedule and update after major changes

### Getting Help

1. **Check Documentation:**
   - Review `.contx/README.md`
   - Check tool-specific documentation


## Advanced Setup

### Git Integration

Add git hooks for automatic sync:

```bash
# Install git hooks
.contx/scripts/install-git-hooks.sh

# Manual hook setup
echo '#!/bin/bash' > .git/hooks/post-commit
echo 'cd .contx && ./scripts/sync-all.sh' >> .git/hooks/post-commit
chmod +x .git/hooks/post-commit
```

### Team Collaboration

1. **Version Control Setup:**
   ```bash
   # Add to version control
   git add .contx/
   git add .augment/ WARP.md GEMINI.md  # Tool-specific files
   git commit -m "Add universal context engineering framework"
   ```

2. **Team Onboarding:**
   ```bash
   # Create team setup script
   cat > setup-team-context.sh << 'EOF'
   #!/bin/bash
   echo "Setting up context engineering for team member..."
   cd .contx
   ./scripts/sync-all.sh
   echo "Setup complete! Choose your preferred AI tool and start coding."
   EOF
   chmod +x setup-team-context.sh
   ```

### Continuous Integration

Add context validation to CI:

```yaml
# .github/workflows/context-validation.yml
name: Context Validation
on: [push, pull_request]
jobs:
  validate-context:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Validate Context Files
        run: |
          # Check required files exist
          test -f .contx/global-context.md
          test -f .contx/templates/01-problem-definition.md
          test -f .contx/templates/02-research.md
          test -f .contx/templates/03-plan.md
          test -f .contx/templates/04-implementation.md
          
          # Validate sync scripts work
          cd .contx
          ./scripts/sync-all.sh
```

## Maintenance

### Weekly Tasks (5 minutes)

1. **Update Domain Contexts:**
   ```bash
   # Review and update patterns discovered during development
   edit .contx/domain-contexts/auth-context.md
   ```

2. **Sync Changes:**
   ```bash
   cd .contx
   ./scripts/sync-all.sh
   ```

### After Major Features (15 minutes)

1. **Document Decisions:**
   ```bash
   # Update global context with architectural decisions
   edit .contx/global-context.md
   ```

2. **Refine Templates:**
   ```bash
   # Improve templates based on what worked/didn't work
   edit .contx/templates/01-problem-definition.md
   edit .contx/templates/02-research.md
   edit .contx/templates/03-plan.md
   edit .contx/templates/04-implementation.md
   ```

### Monthly Review (30 minutes)

1. **Assess Effectiveness:**
   - Review AI assistant performance
   - Identify context gaps
   - Gather team feedback

2. **System Improvements:**
   - Add new domain contexts
   - Create specialized templates
   - Update auto-trigger descriptions

## Troubleshooting

### Common Issues

**Issue**: Auto rules not triggering in Augment
**Solution**: Check the `description` field in rule frontmatter matches task keywords

**Issue**: Warp not loading project context
**Solution**: Verify `WARP.md` exists in project root and current directory

**Issue**: Gemini CLI not finding context
**Solution**: Check `GEMINI.md` exists and run `gemini` from project directory

**Issue**: Templates too verbose for team
**Solution**: Customize templates in `.contx/templates/` to match team preferences

**Issue**: Context getting stale
**Solution**: Set up regular review schedule and update after major changes

### Getting Help

1. **Check Documentation:**
   - Review `.contx/README.md`
   - Check tool-specific documentation

2. **Community Support:**
   - GitHub Issues: Report bugs and request features
   - GitHub Discussions: Community support and questions
   - GitHub Wiki: Detailed documentation and guides

## Success Metrics

Track these indicators to measure framework effectiveness:

- **AI Task Completion Accuracy**: Fewer iterations needed
- **Code Consistency**: Similar patterns across team members
- **Onboarding Time**: New team members productive faster
- **Decision Documentation**: Better architectural decision tracking
- **Context Relevance**: AI provides more relevant suggestions

## Next Steps

1. **Start Small**: Begin with global context and one domain
2. **Iterate**: Refine based on real usage patterns
3. **Expand**: Add more domain contexts as needed
4. **Share**: Contribute improvements back to the community
5. **Scale**: Apply to multiple projects and teams

---

**Remember**: The framework is only as good as the context you maintain. Regular updates and team collaboration are key to success!
