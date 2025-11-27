#!/bin/bash
# Setup script for agent swarm

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="$SCRIPT_DIR/.venv"

echo "Setting up Agent Swarm..."

# Check Python version
if ! command -v python3 &> /dev/null; then
    echo "Error: python3 not found. Please install Python 3.10+"
    exit 1
fi

# Create virtual environment if it doesn't exist
if [ ! -d "$VENV_DIR" ]; then
    echo "Creating virtual environment..."
    python3 -m venv "$VENV_DIR"
fi

# Activate and install dependencies
echo "Installing dependencies..."
source "$VENV_DIR/bin/activate"
pip install --upgrade pip
pip install -r "$SCRIPT_DIR/requirements.txt"

echo ""
echo "✓ Setup complete!"
echo ""
echo "Usage:"
echo "  ./run.sh --task 'Your task here'"
echo ""
echo "Don't forget to set GOOGLE_API_KEY:"
echo "  export GOOGLE_API_KEY='your-key'"

