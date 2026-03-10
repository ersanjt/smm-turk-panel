#!/bin/bash
# ============================================================
#  اسکریپت دیپلوی سرور — بعد از git pull، فایل‌ها را به public_html می‌ریزد
#  کپی کن: cp ~/smm-turk-panel/scripts/deploy-server.sh ~/deploy-smm.sh && chmod +x ~/deploy-smm.sh
#  بعد فقط REPO_DIR و WEB_DIR را با مسیرهای واقعی سرورت عوض کن.
# ============================================================

set -e

# مسیر رپوی گیت (همان پوشه‌ای که با Git Version Control در cPanel یا با git clone ساخته‌ای)
# مثال سرور 92.205.182.143 با یوزر smmturk:
REPO_DIR="$HOME/smm-turk-panel"
# مسیر پوشهٔ وب سایت (معمولاً public_html؛ اگر دامنه جدا داری: $HOME/domains/دامنه/public_html)
WEB_DIR="$HOME/public_html"

if [ ! -d "$REPO_DIR" ]; then
  echo "Error: REPO_DIR not found: $REPO_DIR"
  exit 1
fi

cd "$REPO_DIR"
git fetch origin
git reset --hard origin/main

# کپی به وب؛ config.php را حذف نکن (با --exclude)
rsync -av --delete \
  --exclude='.git' \
  --exclude='config.php' \
  --exclude='deploy-secret.txt' \
  --exclude='tmp/' \
  --exclude='node_modules' \
  --chmod=D755,F644 \
  "$REPO_DIR/" "$WEB_DIR/"

echo "Deploy done: $(date)"
