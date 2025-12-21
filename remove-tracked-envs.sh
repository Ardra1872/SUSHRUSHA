#!/bin/bash
# Run these commands at repo root to stop tracking env/credential files
# This keeps your local files but removes them from the Git index so they won't be pushed.

set -e

echo "Removing tracked env files from git index (will not delete local files)"

git rm --cached .env || true
git rm --cached public/credential.env || true

echo "Committing removal of tracked env files"
git commit -m "Remove tracked env files from repository" || true

echo "Now you can push without credentials being uploaded"

echo "If you previously pushed secrets, you must rewrite history to remove them from remote. Ask for help before doing that."
