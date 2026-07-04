#!/bin/bash
# ============================================================
#  دیپلوی خودکار cPanel — بعد از git pull، فایل‌ها به public_html می‌روند
#  روی سرور کپی کن: cp ~/repositories/smm-turk-panel/scripts/deploy-cpanel.sh ~/deploy-smm.sh
#  سپس: chmod +x ~/deploy-smm.sh
# ============================================================

set -e

REPO_DIR="${REPO_DIR:-$HOME/repositories/smm-turk-panel}"
WEB_DIR="${WEB_DIR:-$HOME/public_html}"

if [ ! -d "$REPO_DIR/.git" ]; then
  echo "Error: Git repo not found: $REPO_DIR"
  exit 1
fi

cd "$REPO_DIR"
git fetch origin main
git reset --hard origin/main

rsync -av --delete \
  --exclude='.git' \
  --exclude='.cpanel.yml' \
  --exclude='config.php' \
  --exclude='deploy-secret.txt' \
  --exclude='tmp/' \
  --exclude='uploads/' \
  --exclude='node_modules' \
  --chmod=D755,F644 \
  "$REPO_DIR/" "$WEB_DIR/"

echo "Deploy done: $(date -Iseconds)"
