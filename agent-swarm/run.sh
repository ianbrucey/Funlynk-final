#!/bin/bash
# Wrapper script to run swarm_cli.py with virtual environment

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="$SCRIPT_DIR/.venv"
PROJECT_ROOT="$SCRIPT_DIR"

# Check if venv exists
if [ ! -d "$VENV_DIR" ]; then
    echo "Error: Virtual environment not found. Run setup first:"
    echo "  ./setup.sh"
    exit 1
fi

# Activate venv and run CLI with project root as default workdir
source "$VENV_DIR/bin/activate"
python "$SCRIPT_DIR/swarm_cli.py" --workdir "$PROJECT_ROOT" "$@"

