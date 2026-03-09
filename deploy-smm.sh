#!/bin/bash
set -e
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_rsa
cd /home/smmturk/repositories/smm-turk-panel
git pull origin main
rsync -av --delete --exclude=".git" --exclude=".cpanel.yml" ./ /home/smmturk/public_html/
