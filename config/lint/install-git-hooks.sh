#!/bin/bash
#
# Enable project git hooks after dependency install.
#

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

if ! git rev-parse --git-dir >/dev/null 2>&1; then
    exit 0
fi

if [ ! -f .githooks/pre-commit ]; then
    exit 0
fi

chmod +x .githooks/pre-commit

HOOKS_PATH="$(git config --get core.hooksPath 2>/dev/null || true)"

if [ "$HOOKS_PATH" = ".githooks" ]; then
    exit 0
fi

if [ -n "$HOOKS_PATH" ]; then
    echo "Git hooks path is already configured ($HOOKS_PATH); skipping setup." >&2
    exit 0
fi

git config core.hooksPath .githooks
echo "Git hooks enabled (.githooks/pre-commit)"
