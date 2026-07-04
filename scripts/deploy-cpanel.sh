#!/bin/bash
# Copy to /home/smmturk/deploy-smm.sh — see docs/AUTO-DEPLOY.md
set -e

CPANEL_USER="${CPANEL_USER:-smmturk}"
REPO_DIR="${REPO_DIR:-/home/${CPANEL_USER}/repositories/smm-turk-panel}"
WEB_DIR="${WEB_DIR:-/home/${CPANEL_USER}/public_html}"

if [ ! -d "$REPO_DIR/.git" ]; then
  echo "Error: Git repo not found: $REPO_DIR"
  exit 1
fi

cd "$REPO_DIR"

# WHM root runs as root; repo owned by smmturk triggers "dubious ownership"
git config --global --add safe.directory "$REPO_DIR" 2>/dev/null || true

if git remote get-url origin 2>/dev/null | grep -q 'git@github.com'; then
  git remote set-url origin "https://github.com/ersanjt/smm-turk-panel.git"
fi

# Public repo — HTTPS fetch works without credentials (no SSH key needed)
if git fetch origin main; then
  git reset --hard origin/main
  echo "Git updated: $(git log -1 --oneline)"
else
  echo "WARN: git fetch failed — syncing current repo copy."
  echo "       Fix: git config --global --add safe.directory $REPO_DIR"
  echo "       Or:  cPanel → Git Version Control → Pull or Deploy"
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
