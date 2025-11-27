#!/usr/bin/env python3
"""
Agent Swarm CLI - Trigger OpenHands agent swarm from terminal.

Usage:
    # Direct task
    python swarm_cli.py --task "Implement feature X"
    
    # From prompt file
    python swarm_cli.py --prompt prompts/feature.md
    
    # Custom working directory (e.g., parent for submodule use)
    python swarm_cli.py --task "Fix bugs" --workdir ../main-project
    
    # Specify model
    python swarm_cli.py --task "Review code" --model gemini/gemini-2.0-flash
"""

import argparse
import os
import sys
from pathlib import Path


def load_env_file():
    """Load environment variables from .env file if it exists."""
    env_file = Path(__file__).parent / ".env"
    if env_file.exists():
        with open(env_file) as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith("#") and "=" in line:
                    key, value = line.split("=", 1)
                    # Only set if not already in environment
                    if key.strip() not in os.environ:
                        os.environ[key.strip()] = value.strip()


def load_task(args) -> str:
    """Load task from --task string or --prompt file."""
    if args.task:
        return args.task
    elif args.prompt:
        prompt_path = Path(args.prompt)
        if not prompt_path.exists():
            print(f"Error: Prompt file not found: {args.prompt}", file=sys.stderr)
            sys.exit(1)
        return prompt_path.read_text().strip()
    else:
        print("Error: Must provide --task or --prompt", file=sys.stderr)
        sys.exit(1)


def resolve_workdir(workdir: str) -> str:
    """Resolve and validate working directory."""
    # Resolve relative paths (e.g., "..", "../main-project")
    resolved = Path(workdir).resolve()
    
    if not resolved.exists():
        print(f"Error: Working directory does not exist: {resolved}", file=sys.stderr)
        sys.exit(1)
    
    if not resolved.is_dir():
        print(f"Error: Not a directory: {resolved}", file=sys.stderr)
        sys.exit(1)
    
    return str(resolved)


def run_swarm(task: str, workdir: str, model: str, budget: float, verbose: bool):
    """Execute the agent swarm."""
    try:
        from pydantic import SecretStr
        from openhands.sdk import LLM, Agent, Conversation, Tool
        from openhands.sdk.tool import register_tool
        from openhands.tools.delegate import DelegateTool
        from openhands.tools.preset.default import get_default_tools
        from openhands.sdk.visualizer import DefaultConversationVisualizer
    except ImportError as e:
        print(f"Error: OpenHands SDK not installed. Run: pip install openhands-ai", file=sys.stderr)
        print(f"Details: {e}", file=sys.stderr)
        sys.exit(1)

    # Get API key
    api_key = os.getenv("GOOGLE_API_KEY") or os.getenv("GEMINI_API_KEY")
    if not api_key:
        print("Error: Set GOOGLE_API_KEY or GEMINI_API_KEY environment variable", file=sys.stderr)
        sys.exit(1)

    if verbose:
        print(f"[Swarm] Model: {model}")
        print(f"[Swarm] Workdir: {workdir}")
        print(f"[Swarm] Budget: ${budget}")
        print(f"[Swarm] Task length: {len(task)} chars")
        print("-" * 50)

    # Configure LLM
    llm = LLM(
        model=model,
        api_key=SecretStr(api_key),
        usage_id="swarm-main",
    )

    # Register and setup tools
    register_tool("DelegateTool", DelegateTool)
    tools = get_default_tools(enable_browser=False)
    tools.append(Tool(name="DelegateTool"))

    # Main agent system prompt
    system_prompt = """You are a Senior Technical Lead managing an agent swarm.

Your capabilities:
1. Analyze the incoming task and break it into parallelizable subtasks
2. Use DelegateTool to spawn sub-agents and delegate work
3. Coordinate results and ensure quality

Guidelines:
- Identify tasks that can run in PARALLEL (e.g., frontend + backend + tests)
- Identify tasks that must be SEQUENTIAL (e.g., schema before migrations)
- Give each sub-agent SPECIFIC, FOCUSED instructions
- After delegation completes, verify the work and summarize results

Available sub-agent roles: coder, reviewer, tester, writer (or spawn generic agents)
"""

    # Create main agent
    main_agent = Agent(llm=llm, tools=tools, system_prompt=system_prompt)

    # Initialize conversation with the TARGET working directory
    conversation = Conversation(
        agent=main_agent,
        workspace=workdir,  # <-- This can be "../main-project" resolved
        visualizer=DefaultConversationVisualizer(name="Swarm"),
    )

    # Execute
    print(f"\n{'='*50}")
    print("AGENT SWARM STARTING")
    print(f"{'='*50}\n")
    
    conversation.send_message(task)
    conversation.run()

    # Report results
    stats = conversation.conversation_stats.get_combined_metrics()
    print(f"\n{'='*50}")
    print("SWARM COMPLETE")
    print(f"{'='*50}")
    print(f"Total cost: ${stats.accumulated_cost:.4f}")
    print(f"Working directory: {workdir}")
    
    return 0


def main():
    parser = argparse.ArgumentParser(
        description="Trigger an OpenHands agent swarm from the terminal.",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__
    )
    
    # Task input (mutually exclusive)
    task_group = parser.add_mutually_exclusive_group(required=True)
    task_group.add_argument("--task", "-t", help="Task description string")
    task_group.add_argument("--prompt", "-p", help="Path to prompt file (.md, .txt)")
    
    # Configuration
    parser.add_argument("--workdir", "-w", default=os.getcwd(),
                        help="Working directory for agents (default: current working dir). "
                             "Can be relative path like '../main-project' or absolute path")
    parser.add_argument("--model", "-m", default="gemini/gemini-1.5-pro-latest",
                        help="LLM model (default: gemini/gemini-1.5-pro-latest)")
    parser.add_argument("--budget", "-b", type=float, default=5.0,
                        help="Budget limit in USD (default: 5.0)")
    parser.add_argument("--verbose", "-v", action="store_true",
                        help="Show detailed output")
    
    args = parser.parse_args()
    
    # Load and validate
    task = load_task(args)
    workdir = resolve_workdir(args.workdir)
    
    # Run swarm
    return run_swarm(task, workdir, args.model, args.budget, args.verbose)


if __name__ == "__main__":
    sys.exit(main())

