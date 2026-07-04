#!/bin/bash
# Cron helper — اگر exec در PHP غیرفعال باشد، webhook فایل deploy.pending می‌سازد
# و این اسکریپت هر دقیقه دیپلوی را اجرا می‌کند.
#
# cPanel → Cron Jobs:
#   * * * * * /home/smmturk/deploy-cron.sh >> /home/smmturk/deploy.log 2>&1

set -e

FLAG="$HOME/deploy.pending"
DEPLOY="$HOME/deploy-smm.sh"

[ -f "$FLAG" ] || exit 0
rm -f "$FLAG"

if [ ! -x "$DEPLOY" ]; then
  echo "$(date -Iseconds) ERROR: $DEPLOY not found or not executable" >> "$HOME/deploy.log"
  exit 1
fi

bash "$DEPLOY"
