#!/bin/bash
# Copy to /home/smmturk/deploy-smm.sh — see docs/AUTO-DEPLOY.md
set -e

CPANEL_USER="${CPANEL_USER:-smmturk}"
REPO_DIR="${REPO_DIR:-/home/${CPANEL_USER}/repositories/smm-turk-panel}"
WEB_DIR="${WEB_DIR:-/home/${CPANEL_USER}/public_html}"
GITHUB_REPO="https://github.com/ersanjt/smm-turk-panel"
GITHUB_ARCHIVE="${GITHUB_REPO}/archive/refs/heads/main.tar.gz"

RSYNC_EXCLUDES=(
  --exclude='.git'
  --exclude='.cpanel.yml'
  --exclude='config.php'
  --exclude='deploy-secret.txt'
  --exclude='deploy-smm.sh'
  --exclude='deploy-cron.sh'
  --exclude='tmp/'
  --exclude='uploads/'
  --exclude='node_modules'
)

rsync_to_web() {
  local src="$1"
  rsync -av --delete "${RSYNC_EXCLUDES[@]}" --chmod=D755,F644 "$src" "$WEB_DIR/"
}

git_updated=0

if [ -d "$REPO_DIR/.git" ]; then
  cd "$REPO_DIR"
  git config --global --add safe.directory "$REPO_DIR" 2>/dev/null || true

  if git remote get-url origin 2>/dev/null | grep -q 'git@github.com'; then
    git remote set-url origin "${GITHUB_REPO}.git"
  fi

  # No credential prompt — works for public repos without login
  if GIT_TERMINAL_PROMPT=0 git -c credential.helper= fetch origin main 2>/dev/null; then
    git reset --hard origin/main
    echo "Git updated: $(git log -1 --oneline)"
    rsync_to_web "$REPO_DIR/"
    git_updated=1
  else
    echo "WARN: git fetch failed (repo private or cached bad credentials)."
  fi
else
  echo "WARN: Git repo not found at $REPO_DIR"
fi

# Fallback: download public archive without git auth
if [ "$git_updated" -eq 0 ]; then
  echo "Trying GitHub archive download (no login)..."
  tmp_dir=$(mktemp -d)
  trap 'rm -rf "$tmp_dir"' EXIT

  if curl -fsSL "$GITHUB_ARCHIVE" -o "$tmp_dir/main.tar.gz"; then
    tar -xzf "$tmp_dir/main.tar.gz" -C "$tmp_dir"
    extract_dir="$tmp_dir/smm-turk-panel-main"
    if [ ! -d "$extract_dir" ]; then
      extract_dir=$(find "$tmp_dir" -mindepth 1 -maxdepth 1 -type d | head -1)
    fi
    if [ -d "$extract_dir" ]; then
      rsync_to_web "$extract_dir/"
      echo "Deployed from GitHub archive (main branch)."
    else
      echo "Error: archive extracted but folder not found."
      exit 1
    fi
  else
    echo "Error: could not download $GITHUB_ARCHIVE"
    echo "  - If repo is private: GitHub → Settings → General → Change visibility → Public"
    echo "  - Or: cPanel → Git Version Control → Pull or Deploy"
    echo "  - Or: create a GitHub PAT and run:"
    echo "      git -c credential.helper= fetch https://TOKEN@github.com/ersanjt/smm-turk-panel.git main"
    exit 1
  fi
fi

echo "Deploy done: $(date -Iseconds)"
