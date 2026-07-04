#!/bin/bash
# Cron helper — webhook creates deploy.pending; this runs deploy-smm.sh every minute
#
# cPanel → Cron Jobs:
#   * * * * * /home/smmturk/deploy-cron.sh >> /home/smmturk/deploy.log 2>&1

set -e

CPANEL_USER="${CPANEL_USER:-smmturk}"
HOME_DIR="/home/${CPANEL_USER}"
FLAG="${HOME_DIR}/deploy.pending"
DEPLOY="${HOME_DIR}/deploy-smm.sh"
LOG="${HOME_DIR}/deploy.log"

[ -f "$FLAG" ] || exit 0
rm -f "$FLAG"

if [ ! -x "$DEPLOY" ]; then
  echo "$(date -Iseconds) ERROR: $DEPLOY not found or not executable" >> "$LOG"
  exit 1
fi

bash "$DEPLOY"
