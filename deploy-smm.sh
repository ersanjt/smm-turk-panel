#!/bin/bash
# Server deploy — copy to /home/smmturk/deploy-smm.sh (not served from public_html)
set -e

CPANEL_USER="${CPANEL_USER:-smmturk}"
REPO_DIR="${REPO_DIR:-/home/${CPANEL_USER}/repositories/smm-turk-panel}"
WEB_DIR="${WEB_DIR:-/home/${CPANEL_USER}/public_html}"

if [ ! -d "$REPO_DIR/.git" ]; then
  echo "Error: Git repo not found: $REPO_DIR"
  exit 1
fi

cd "$REPO_DIR"

git config --global --add safe.directory "$REPO_DIR" 2>/dev/null || true

if git remote get-url origin 2>/dev/null | grep -q 'git@github.com'; then
  git remote set-url origin "https://github.com/ersanjt/smm-turk-panel.git"
fi

if ! git fetch origin main 2>/dev/null; then
  echo "WARN: git fetch failed — syncing current repo copy."
  echo "       Update repo first: cPanel → Git Version Control → Pull or Deploy"
else
  git reset --hard origin/main
fi

rsync -av --delete \
  --exclude='.git' \
  --exclude='.cpanel.yml' \
  --exclude='config.php' \
  --exclude='deploy-secret.txt' \
  --exclude='deploy-smm.sh' \
  --exclude='deploy-cron.sh' \
  --exclude='tmp/' \
  --exclude='uploads/' \
  --exclude='node_modules' \
  --chmod=D755,F644 \
  "$REPO_DIR/" "$WEB_DIR/"

echo "Deploy done: $(date -Iseconds)"
