#!/usr/bin/env bash
#
# Runs upstream/sync.sh in a throwaway worktree of the current HEAD and prints a summary — which
# files it would touch, how many would carry conflict markers, and the diff stat — without
# touching this checkout. The worktree is removed afterwards.
#
#   upstream/rehearse-sync.sh <package> <to-tag>
#   upstream/rehearse-sync.sh admin-bundle 4.44.0

set -euo pipefail

if [ $# -ne 2 ]; then
    echo "usage: $0 <package> <to-tag>" >&2
    exit 64
fi

root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
scratch=$(mktemp -d)
trap 'git -C "$root" worktree remove --force "$scratch" 2>/dev/null || true; rm -rf "$scratch"' EXIT

git -C "$root" worktree add --quiet --detach "$scratch" HEAD
ln -s "$root/vendor" "$scratch/vendor"

(cd "$scratch" && upstream/sync.sh "$1" "$2")

echo
echo "#### What the sync would change"
echo
git -C "$scratch" add -A
git -C "$scratch" diff --cached --stat
echo
echo "conflict markers in: $(git -C "$scratch" diff --cached | grep -c '^+<<<<<<<' || true) files"
