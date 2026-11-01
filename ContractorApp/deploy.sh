#!/bin/bash
# ContractorApp deployment script for buildmate.tech (Hostinger)
# Run this in your terminal: bash deploy.sh
# You will be prompted for your hPanel password.

set -e

SSH_HOST="srv839.hstgr.io"
SSH_PORT="65002"
SSH_USER="u540637169"
REMOTE_DIR="/home/u540637169/domains/buildmate.tech/public_html"
REPO="https://github.com/SaifuWayanad/ContractorMiniERP.git"
APP_SUBDIR="ContractorApp"

echo "========================================"
echo "  ContractorApp → buildmate.tech deploy"
echo "========================================"
echo ""
echo "Host : $SSH_HOST:$SSH_PORT"
echo "User : $SSH_USER"
echo "Dir  : $REMOTE_DIR"
echo ""

# Upload production .env first
echo "[1/4] Uploading .env.production as .env on server..."
scp -P "$SSH_PORT" \
  "$(dirname "$0")/.env.production" \
  "$SSH_USER@$SSH_HOST:$REMOTE_DIR/$APP_SUBDIR/.env"

echo ""
echo "[2/4] Deploying code via git on server..."
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" bash << REMOTE
  set -e
  cd "$REMOTE_DIR"

  if [ -d "$APP_SUBDIR/.git" ]; then
    echo "  → Pulling latest code..."
    cd "$APP_SUBDIR"
    git pull origin main
  else
    echo "  → Cloning repository..."
    git clone --depth=1 "$REPO" "$APP_SUBDIR"
    cd "$APP_SUBDIR"
  fi

  echo "  → Done."
REMOTE

echo ""
echo "[3/4] Running composer and artisan commands..."
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" bash << REMOTE
  set -e
  cd "$REMOTE_DIR/$APP_SUBDIR"

  echo "  → composer install..."
  composer install --no-dev --optimize-autoloader --no-interaction

  echo "  → php artisan migrate..."
  php artisan migrate --force

  echo "  → Caching config/routes/views..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache

  echo "  → Storage link..."
  php artisan storage:link 2>/dev/null || true

  echo "  → Setting permissions..."
  chmod -R 775 storage bootstrap/cache
  chown -R "$SSH_USER":nobody storage bootstrap/cache 2>/dev/null || true
REMOTE

echo ""
echo "[4/4] Pointing web root to ContractorApp/public..."
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" bash << REMOTE
  # Check if public_html already contains Laravel public files
  if [ ! -f "$REMOTE_DIR/index.php" ]; then
    echo "  → Symlinking ContractorApp/public to public_html index..."
    cp -f "$REMOTE_DIR/$APP_SUBDIR/public/index.php" "$REMOTE_DIR/index.php"
    cp -f "$REMOTE_DIR/$APP_SUBDIR/public/.htaccess" "$REMOTE_DIR/.htaccess" 2>/dev/null || true
    # Update the auto-generated path in index.php to point to correct bootstrap
    sed -i "s|__DIR__.'/../vendor|__DIR__.'/$APP_SUBDIR/vendor|g" "$REMOTE_DIR/index.php"
    sed -i "s|__DIR__.'/../bootstrap|__DIR__.'/$APP_SUBDIR/bootstrap|g" "$REMOTE_DIR/index.php"
  fi
REMOTE

echo ""
echo "========================================"
echo "  Deployment complete!"
echo "  Visit: https://buildmate.tech"
echo "========================================"
