#!/bin/bash
# نسخهٔ کوتاه — همان scripts/deploy-cpanel.sh
# روی سرور: cp این فایل به ~/deploy-smm.sh && chmod +x ~/deploy-smm.sh
set -e
REPO_DIR="${REPO_DIR:-$HOME/repositories/smm-turk-panel}"
WEB_DIR="${WEB_DIR:-$HOME/public_html}"
[ -d "$REPO_DIR/.git" ] || { echo "Error: $REPO_DIR not found"; exit 1; }
cd "$REPO_DIR"
git fetch origin main && git reset --hard origin/main
rsync -av --delete --exclude='.git' --exclude='.cpanel.yml' --exclude='config.php' --exclude='deploy-secret.txt' --exclude='tmp/' --exclude='uploads/' --chmod=D755,F644 "$REPO_DIR/" "$WEB_DIR/"
echo "Deploy done: $(date -Iseconds)"
