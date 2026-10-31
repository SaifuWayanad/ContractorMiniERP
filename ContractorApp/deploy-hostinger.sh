#!/usr/bin/env bash
# Package this Laravel app and deploy it to Hostinger shared hosting (overwrites DOMAIN's public_html).
#
#   export HOSTINGER_API_TOKEN=...      # hPanel > API
#   export DB_PASSWORD=...              # password of the MySQL user below (prompted if unset)
#   export ADMIN_PASSWORD=...           # optional; generated and printed if unset
#   ./deploy-hostinger.sh
#
# Re-running redeploys the code and applies new migrations. Seeding only runs once (guarded by storage/.installed).
set -euo pipefail

DOMAIN="${DOMAIN:-buildmate.tech}"
HUSER="${HUSER:-u540637169}"
DB_NAME="${DB_NAME:-u540637169_bm}"
DB_USER="${DB_USER:-u540637169_bmu}"
PHP_BIN="${PHP_BIN:-/opt/alt/php84/usr/bin/php}"
APP_URL="https://${DOMAIN}"
ADMIN_EMAIL="${ADMIN_EMAIL:-pixamazecreative@gmail.com}"
API="https://developers.hostinger.com/api/hosting/v1"
APP_DIR="/home/${HUSER}/domains/${DOMAIN}/public_html"

SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
STAGE="$WORK/stage"

: "${HOSTINGER_API_TOKEN:?Set HOSTINGER_API_TOKEN}"
if [ -z "${DB_PASSWORD:-}" ]; then read -r -s -p "MySQL password for ${DB_USER}: " DB_PASSWORD; echo; fi
GENERATED_ADMIN=0
if [ -z "${ADMIN_PASSWORD:-}" ]; then
  ADMIN_PASSWORD="Bm$(openssl rand -hex 7)!a"; GENERATED_ADMIN=1
fi

for bin in php composer rsync zip curl python3 openssl; do
  command -v "$bin" >/dev/null || { echo "Missing required tool: $bin" >&2; exit 1; }
done

api() { # method path [json-body]; path is relative to $API
  local method="$1" path="$2" body="${3:-}" out status
  out="$WORK/api.out"
  if [ -n "$body" ]; then
    status="$(curl -sS -o "$out" -w '%{http_code}' -X "$method" -H "Authorization: Bearer $HOSTINGER_API_TOKEN" -H "Content-Type: application/json" -d "$body" "$API$path")"
  else
    status="$(curl -sS -o "$out" -w '%{http_code}' -X "$method" -H "Authorization: Bearer $HOSTINGER_API_TOKEN" "$API$path")"
  fi
  if [ "$status" -ge 400 ]; then
    echo "API error: $method $path -> HTTP $status" >&2
    head -c 800 "$out" >&2; echo >&2
    return 1
  fi
  cat "$out"
}
json_get() { python3 -I -c "import sys,json; d=json.load(sys.stdin); print($1)"; }
cron_body() { python3 -I -c "import json,sys; print(json.dumps({'time':'* * * * *','command':sys.argv[1]}))" "$1"; }

echo "==> Staging a production copy"
mkdir -p "$STAGE"
rsync -a \
  --exclude=/vendor --exclude=/node_modules --exclude=/.git --exclude=/tests \
  --exclude=/.env --exclude=/database/database.sqlite --exclude=/*.sh \
  --exclude=/AGENTS.md --exclude=/CLAUDE.md --exclude=/boost.json --exclude=/phpunit.xml --exclude='/*.zip' \
  --exclude='/storage/logs/*' --exclude='/storage/framework/sessions/*' \
  --exclude='/storage/framework/cache/data/*' --exclude='/storage/framework/views/*' \
  --exclude='/bootstrap/cache/*.php' --exclude=.DS_Store \
  "$SRC/" "$STAGE/"
mkdir -p "$STAGE/bootstrap/cache" "$STAGE/storage/logs" "$STAGE/storage/app/public" \
         "$STAGE/storage/framework/sessions" "$STAGE/storage/framework/cache/data" "$STAGE/storage/framework/views"

echo "==> Installing production dependencies"
( cd "$STAGE" && composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --quiet )

# BuildTrack API (Laravel) for projectops.buildmate.tech. Its folder is public_html/projectops, which
# this deploy replaces, so the API is packed into the same archive. Set PROJECTOPS_SRC to enable it.
if [ -n "${PROJECTOPS_SRC:-}" ]; then
  echo "==> Staging the BuildTrack API into projectops/"
  mkdir -p "$STAGE/projectops"
  rsync -a \
    --exclude=/vendor --exclude=/.env --exclude=/database/database.sqlite --exclude=/tests \
    --exclude=/.phpunit.result.cache --exclude=/README.md --exclude=/AGENTS.md --exclude=/CLAUDE.md \
    --exclude='/storage/logs/*' --exclude='/storage/framework/sessions/*' --exclude='/storage/framework/cache/data/*' \
    --exclude='/storage/framework/views/*' --exclude='/bootstrap/cache/*.php' --exclude=.DS_Store \
    "$PROJECTOPS_SRC/" "$STAGE/projectops/"
  mkdir -p "$STAGE/projectops/bootstrap/cache" "$STAGE/projectops/storage/logs" "$STAGE/projectops/storage/app/public" \
           "$STAGE/projectops/storage/framework/sessions" "$STAGE/projectops/storage/framework/cache/data" "$STAGE/projectops/storage/framework/views"
  ( cd "$STAGE/projectops" && composer install --no-dev --optimize-autoloader --no-interaction --quiet )

  # The subdomain's document root is projectops/, so send every request to public/.
  cat > "$STAGE/projectops/.htaccess" <<'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
EOF

  API_KEY="$(cat "$HOME/.buildtrack-api-app-key")"
  API_DB_PASSWORD="$(cat "$HOME/.buildtrack-db-password")"
  cat > "$STAGE/projectops/.env" <<EOF
APP_NAME=BuildTrack
APP_ENV=production
APP_KEY=${API_KEY}
APP_DEBUG=false
APP_URL=https://projectops.${DOMAIN}
APP_TIMEZONE=UTC
LOG_CHANNEL=single
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_HOST=srv839.hstgr.io
DB_PORT=3306
DB_DATABASE=u540637169_buildtrack
DB_USERNAME=u540637169_buildtrack
DB_PASSWORD=${API_DB_PASSWORD}
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
EOF
fi

# BuildTrack frontend (Laravel) for buildtrack.buildmate.tech. Packed into the same archive for the same
# reason as the API above. Set BUILDTRACK_SRC to enable it.
if [ -n "${BUILDTRACK_SRC:-}" ]; then
  echo "==> Staging the BuildTrack frontend into buildtrack/"
  mkdir -p "$STAGE/buildtrack"
  rsync -a \
    --exclude=/vendor --exclude=/node_modules --exclude=/.env --exclude=/database/database.sqlite --exclude=/tests \
    --exclude=/.phpunit.result.cache --exclude=/README.md --exclude=/AGENTS.md --exclude=/CLAUDE.md \
    --exclude='/storage/logs/*' --exclude='/storage/framework/sessions/*' --exclude='/storage/framework/cache/data/*' \
    --exclude='/storage/framework/views/*' --exclude='/bootstrap/cache/*.php' --exclude=.DS_Store \
    "$BUILDTRACK_SRC/" "$STAGE/buildtrack/"
  mkdir -p "$STAGE/buildtrack/bootstrap/cache" "$STAGE/buildtrack/storage/logs" "$STAGE/buildtrack/storage/app/public" \
           "$STAGE/buildtrack/storage/framework/sessions" "$STAGE/buildtrack/storage/framework/cache/data" "$STAGE/buildtrack/storage/framework/views"
  ( cd "$STAGE/buildtrack" && composer install --no-dev --optimize-autoloader --no-interaction --quiet )

  cat > "$STAGE/buildtrack/.htaccess" <<'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
EOF

  FE_KEY="$(cat "$HOME/.buildtrack-frontend-app-key")"
  cat > "$STAGE/buildtrack/.env" <<EOF
APP_NAME=BuildTrack
APP_ENV=production
APP_KEY=${FE_KEY}
APP_DEBUG=false
APP_URL=https://buildtrack.${DOMAIN}
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
LOG_CHANNEL=single
LOG_LEVEL=warning
BACKEND_API_BASE_URL=https://projectops.${DOMAIN}/api
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
EOF
fi

# The APP_KEY is generated once and kept locally so redeploys don't invalidate sessions/encrypted data.
KEY_FILE="$HOME/.buildmate-app-key"
if [ ! -s "$KEY_FILE" ]; then
  ( umask 077; php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$KEY_FILE" )
fi
APP_KEY="$(cat "$KEY_FILE")"

cat > "$STAGE/.env" <<EOF
APP_NAME=BuildMate
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=${APP_URL}
LOG_CHANNEL=single
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log
FCM_CREDENTIALS=${APP_DIR}/storage/app/firebase/service-account.json
EOF

# Serve from /public; nothing else in the web root is reachable.
cat > "$STAGE/.htaccess" <<'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(\.env|composer\.(json|lock)|artisan)$ - [F,L]
    RewriteRule \.zip$ - [F,L]
    RewriteRule ^vendor/bootstrap/(.*)$ public/vendor/bootstrap/$1 [L]
    RewriteRule ^(app|bootstrap|config|database|resources|routes|storage|vendor)(/|$) - [F,L]
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
EOF

# One-off admin seeder (lives outside public/, so it is never web-reachable; removed after install).
ADMIN_HASH="$(php -r 'echo password_hash($argv[1], PASSWORD_BCRYPT);' "$ADMIN_PASSWORD")"
cat > "$STAGE/database/seeders/ProductionAdminSeeder.php" <<EOF
<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('username', 'admin')->exists()) {
            return;
        }

        \$user = new User(['name' => 'Administrator', 'username' => 'admin', 'email' => '${ADMIN_EMAIL}']);
        \$user->password = '${ADMIN_HASH}';
        \$user->role_id = Role::where('slug', 'super-admin')->value('id');
        \$user->status = UserStatus::Active;
        \$user->email_verified_at = now();
        \$user->save();
    }
}
EOF

( cd "$STAGE" && php artisan package:discover --quiet )

# Bundle install script so the cron command stays under 255 chars.
cat > "$STAGE/install.sh" <<INSTALLEOF
#!/usr/bin/env bash
set -e
cd ${APP_DIR}
rm -f bm_*.zip
# Pending migrations run on every deploy (migrate is a no-op when up to date).
${PHP_BIN} artisan migrate --force
if [ ! -f storage/.installed ]; then
  ${PHP_BIN} artisan db:seed --force
  ${PHP_BIN} artisan db:seed --class=ProductionAdminSeeder --force
  touch storage/.installed
fi
ln -sfn ${APP_DIR}/storage/app/public ${APP_DIR}/public/storage
${PHP_BIN} artisan optimize:clear
${PHP_BIN} artisan config:cache
${PHP_BIN} artisan route:cache
${PHP_BIN} artisan view:cache
if [ -d projectops ]; then
  cd projectops
  ${PHP_BIN} artisan migrate --force
  ${PHP_BIN} artisan optimize:clear
  ${PHP_BIN} artisan config:cache
  ${PHP_BIN} artisan route:cache
  cd ..
fi
if [ -d buildtrack ]; then
  cd buildtrack
  ${PHP_BIN} artisan optimize:clear
  ${PHP_BIN} artisan config:cache
  cd ..
fi
rm -f database/seeders/ProductionAdminSeeder.php install.sh
echo INSTALL_OK
INSTALLEOF

ZIP_NAME="bm_$(openssl rand -hex 6).zip"
echo "==> Zipping ($ZIP_NAME)"
( cd "$STAGE" && zip -qr "$WORK/$ZIP_NAME" . )
SIZE="$(wc -c < "$WORK/$ZIP_NAME" | tr -d ' ')"
echo "    $(du -h "$WORK/$ZIP_NAME" | cut -f1) archive"

echo "==> Uploading to ${DOMAIN}"
CREDS="$(api POST /files/upload-urls "{\"username\":\"$HUSER\",\"domain\":\"$DOMAIN\"}")"
echo "    Upload-urls response: $CREDS"
UP_URL="$(echo "$CREDS" | json_get "d['url']")"; UP_URL="${UP_URL%/}"
AUTH="$(echo "$CREDS" | json_get "d['auth_key']")"
RAUTH="$(echo "$CREDS" | json_get "d['rest_auth_key']")"
# Extract server-side path if provided, fall back to home directory
UP_PATH="$(echo "$CREDS" | python3 -I -c "import sys,json; d=json.load(sys.stdin); print(d.get('path', '/home/${HUSER}/').rstrip('/'))" 2>/dev/null || echo "/home/${HUSER}")"
echo "    Files will be stored under: $UP_PATH"
TUS=(--http1.1 --tlsv1.2 -sS -H "X-Auth: $AUTH" -H "X-Auth-Rest: $RAUTH" -H "Tus-Resumable: 1.0.0")
CHUNK=$((1024 * 1024))
for attempt in 1 2 3 4 5; do
  curl "${TUS[@]}" -o /dev/null -f -X POST "$UP_URL/$ZIP_NAME?override=true" \
    -H "Upload-Length: $SIZE" -H "Upload-Offset: 0" && break
  [ "$attempt" = 5 ] && { echo "Could not create the upload" >&2; exit 1; }
  sleep 2
done
# Sends the archive from byte $1 to the end. Hostinger's Upload-Offset reply is the only proof a chunk
# was stored, so we track the last one it sent and never assume a chunk landed.
LAST_STORED=""
send_from() {
  OFFSET="$1"
  while [ "$OFFSET" -lt "$SIZE" ]; do
    python3 -I -c "import sys; f=open(sys.argv[1],'rb'); f.seek(int(sys.argv[2])); sys.stdout.buffer.write(f.read(int(sys.argv[3])))" "$WORK/$ZIP_NAME" "$OFFSET" "$CHUNK" > "$WORK/chunk"
    CHUNK_LEN="$(wc -c < "$WORK/chunk" | tr -d ' ')"
    SENT=0
    for attempt in 1 2 3 4 5 6; do
      HDRS="$(curl "${TUS[@]}" -f -D - -o /dev/null -X PATCH "$UP_URL/$ZIP_NAME?override=true" \
        -H "Content-Type: application/offset+octet-stream" -H "Upload-Offset: $OFFSET" \
        --data-binary "@$WORK/chunk" 2>/dev/null)" && {
        NEW="$(echo "$HDRS" | tr -d '\r' | awk -F': ' 'tolower($1)=="upload-offset"{print $2}' | tail -n 1)"
        if [ -n "${NEW:-}" ]; then LAST_STORED="$NEW"; OFFSET="$NEW"; else OFFSET=$((OFFSET + CHUNK_LEN)); fi
        SENT=1; break; }
      sleep 2
    done
    [ "$SENT" != 0 ] || { echo "Upload failed at byte $OFFSET of $SIZE" >&2; exit 1; }
    printf '\r    %d%%' $((OFFSET * 100 / SIZE))
  done
  echo
}

send_from 0

# Hostinger must confirm the full size in its own reply before the deploy is requested.
if [ "${LAST_STORED:-}" != "$SIZE" ]; then
  echo "Hostinger confirmed ${LAST_STORED:-0} of $SIZE bytes. The site was not changed." >&2
  exit 1
fi
echo "    Hostinger confirmed all $SIZE bytes"

echo "==> Setting PHP 8.4"
api PATCH "/accounts/$HUSER/websites/$DOMAIN/php/version" '{"version":"8.4"}' >/dev/null

echo "==> Deploying (this replaces everything in public_html)"
ARCHIVE_PATH="${UP_PATH}/${ZIP_NAME}"
echo "    Using archive_path: $ARCHIVE_PATH"
# Try full path first, fall back to bare filename if rejected
for attempt in 1 2 3 4 5; do
  if api POST "/accounts/$HUSER/websites/$DOMAIN/deploy" "{\"archive_path\":\"$ARCHIVE_PATH\"}" >/dev/null; then break; fi
  # On second attempt also try bare filename in case API changed
  if [ "$attempt" = 2 ]; then
    echo "    Retrying with bare filename as archive_path..."
    if api POST "/accounts/$HUSER/websites/$DOMAIN/deploy" "{\"archive_path\":\"$ZIP_NAME\"}" >/dev/null; then break; fi
  fi
  [ "$attempt" = 5 ] && { echo "Deploy call kept failing; the site was not changed." >&2; exit 1; }
  echo "    deploy call failed, retrying in 60s ($attempt/5)"; sleep 60
done

echo "==> Running install tasks on the server via a one-off cron job"
INSTALL_CMD="cd $APP_DIR && bash install.sh"

cron_items() { python3 -I -c "import sys,json; d=json.load(sys.stdin); print(__import__('json').dumps(d if isinstance(d,list) else d.get('data',[])))"; }

for uid in $(api GET "/accounts/$HUSER/cron-jobs" | cron_items | python3 -I -c "import sys,json; [print(c['uid']) for c in json.load(sys.stdin) if 'install.sh' in c.get('command','')]"); do
  api DELETE "/accounts/$HUSER/cron-jobs/$uid" >/dev/null || true
done
api POST "/accounts/$HUSER/cron-jobs" "$(cron_body "$INSTALL_CMD")" >/dev/null
JOB_UID="$(api GET "/accounts/$HUSER/cron-jobs" | cron_items | python3 -I -c "import sys,json; print(next(c['uid'] for c in json.load(sys.stdin) if 'install.sh' in c['command']))")"

OK=0; OUT=""
for i in $(seq 1 20); do
  sleep 15
  RAW="$(api GET "/accounts/$HUSER/cron-jobs/$JOB_UID/output" 2>/dev/null || true)"
  OUT="$(echo "$RAW" | python3 -I -c "
import sys, json
try:
    d = json.load(sys.stdin)
    # handle {output:...} or {data:{output:...}}
    if isinstance(d, dict):
        print(d.get('output') or (d.get('data') or {}).get('output',''))
except Exception:
    pass
" 2>/dev/null || true)"
  if echo "$OUT" | grep -q INSTALL_OK; then OK=1; break; fi
  printf '\r    waiting for cron job... (%d/20, ~%ds elapsed)' "$i" $((i * 15))
done
echo
api DELETE "/accounts/$HUSER/cron-jobs/$JOB_UID" >/dev/null || true
if [ "$OK" != 1 ]; then
  echo "" >&2
  echo "Install task did not complete automatically. Last output:" >&2
  echo "${OUT:-<none>}" >&2
  echo "" >&2
  echo "Run these commands manually via SSH (ssh -p 65002 $HUSER@srv839.hstgr.io):" >&2
  echo "  cd $APP_DIR" >&2
  echo "  $PHP_BIN artisan migrate --force" >&2
  echo "  $PHP_BIN artisan config:cache && $PHP_BIN artisan route:cache && $PHP_BIN artisan view:cache" >&2
  echo "  ln -sfn $APP_DIR/storage/app/public $APP_DIR/public/storage" >&2
  echo "  touch storage/.installed" >&2
  echo "" >&2
  echo "The site files are deployed — only the post-install step is needed." >&2
fi

echo "==> Adding the scheduler cron (meeting reminders)"
SCHED_CMD="cd $APP_DIR && $PHP_BIN artisan schedule:run >> /dev/null 2>&1"
if ! api GET "/accounts/$HUSER/cron-jobs" | grep -q 'schedule:run'; then
  api POST "/accounts/$HUSER/cron-jobs" "$(cron_body "$SCHED_CMD")" >/dev/null
fi

echo "==> Checking ${APP_URL}/login"
CODE="$(curl -s -o /dev/null -w '%{http_code}' "$APP_URL/login" || true)"
echo "    HTTP $CODE"

echo
echo "Done: $APP_URL"
echo "Admin login: username 'admin'"
if [ "$GENERATED_ADMIN" = 1 ]; then echo "Admin password (generated, change it after first login): $ADMIN_PASSWORD"; fi
