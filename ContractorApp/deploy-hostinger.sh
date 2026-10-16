#!/usr/bin/env bash
# Package this Laravel app and deploy it to Hostinger shared hosting (overwrites DOMAIN's public_html).
#
#   export HOSTINGER_API_TOKEN=...      # hPanel > API
#   export DB_PASSWORD=...              # password of the MySQL user below (prompted if unset)
#   export ADMIN_PASSWORD=...           # optional; generated and printed if unset
#   ./deploy-hostinger.sh
#
# Re-running redeploys the code. Migrations/seeding only run once (guarded by storage/.installed).
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
  --exclude=/AGENTS.md --exclude=/CLAUDE.md --exclude=/boost.json --exclude=/phpunit.xml \
  --exclude='/storage/logs/*' --exclude='/storage/framework/sessions/*' \
  --exclude='/storage/framework/cache/data/*' --exclude='/storage/framework/views/*' \
  --exclude='/bootstrap/cache/*.php' --exclude=.DS_Store \
  "$SRC/" "$STAGE/"
mkdir -p "$STAGE/bootstrap/cache" "$STAGE/storage/logs" "$STAGE/storage/app/public" \
         "$STAGE/storage/framework/sessions" "$STAGE/storage/framework/cache/data" "$STAGE/storage/framework/views"

echo "==> Installing production dependencies"
( cd "$STAGE" && composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --quiet )

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
if [ ! -f storage/.installed ]; then
  ${PHP_BIN} artisan migrate --force
  ${PHP_BIN} artisan db:seed --force
  ${PHP_BIN} artisan db:seed --class=ProductionAdminSeeder --force
  touch storage/.installed
fi
ln -sfn ${APP_DIR}/storage/app/public ${APP_DIR}/public/storage
${PHP_BIN} artisan optimize:clear
${PHP_BIN} artisan config:cache
${PHP_BIN} artisan route:cache
${PHP_BIN} artisan view:cache
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
UP_URL="$(echo "$CREDS" | json_get "d['url']")"; UP_URL="${UP_URL%/}"
AUTH="$(echo "$CREDS" | json_get "d['auth_key']")"
RAUTH="$(echo "$CREDS" | json_get "d['rest_auth_key']")"
TUS=(--http1.1 --tlsv1.2 -sS -H "X-Auth: $AUTH" -H "X-Auth-Rest: $RAUTH" -H "Tus-Resumable: 1.0.0")
CHUNK=$((1024 * 1024))
for attempt in 1 2 3 4 5; do
  curl "${TUS[@]}" -o /dev/null -f -X POST "$UP_URL/$ZIP_NAME?override=true" \
    -H "Upload-Length: $SIZE" -H "Upload-Offset: 0" && break
  [ "$attempt" = 5 ] && { echo "Could not create the upload" >&2; exit 1; }
  sleep 2
done
OFFSET=0
while [ "$OFFSET" -lt "$SIZE" ]; do
  python3 -I -c "import sys; f=open(sys.argv[1],'rb'); f.seek(int(sys.argv[2])); sys.stdout.buffer.write(f.read(int(sys.argv[3])))" "$WORK/$ZIP_NAME" "$OFFSET" "$CHUNK" > "$WORK/chunk"
  SENT=0
  for attempt in 1 2 3 4 5 6; do
    HDRS="$(curl "${TUS[@]}" -f -D - -o /dev/null -X PATCH "$UP_URL/$ZIP_NAME?override=true" \
      -H "Content-Type: application/offset+octet-stream" -H "Upload-Offset: $OFFSET" \
      --data-binary "@$WORK/chunk" 2>/dev/null)" && {
      NEW="$(echo "$HDRS" | tr -d '\r' | awk -F': ' 'tolower($1)=="upload-offset"{print $2}' | tail -n 1)"
      OFFSET="${NEW:-$((OFFSET + $(wc -c < "$WORK/chunk" | tr -d ' ')))}"; SENT=1; break; }
    REMOTE="$(curl "${TUS[@]}" -I "$UP_URL/$ZIP_NAME?override=true" 2>/dev/null | tr -d '\r' | awk -F': ' 'tolower($1)=="upload-offset"{print $2}' | tail -n 1)"
    if [ -n "${REMOTE:-}" ] && [ "$REMOTE" != "$OFFSET" ]; then OFFSET="$REMOTE"; SENT=2; break; fi
    sleep 2
  done
  [ "$SENT" != 0 ] || { echo "Upload failed at byte $OFFSET of $SIZE" >&2; exit 1; }
  printf '\r    %d%%' $((OFFSET * 100 / SIZE))
done
echo

echo "==> Setting PHP 8.4"
api PATCH "/accounts/$HUSER/websites/$DOMAIN/php/version" '{"version":"8.4"}' >/dev/null

echo "==> Deploying (this replaces everything in public_html)"
api POST "/accounts/$HUSER/websites/$DOMAIN/deploy" "{\"archive_path\":\"$ZIP_NAME\"}" >/dev/null

echo "==> Running install tasks on the server via a one-off cron job"
INSTALL_CMD="cd $APP_DIR && bash install.sh"

cron_items() { python3 -I -c "import sys,json; d=json.load(sys.stdin); print(__import__('json').dumps(d if isinstance(d,list) else d.get('data',[])))"; }

for uid in $(api GET "/accounts/$HUSER/cron-jobs" | cron_items | python3 -I -c "import sys,json; [print(c['uid']) for c in json.load(sys.stdin) if 'install.sh' in c.get('command','')]"); do
  api DELETE "/accounts/$HUSER/cron-jobs/$uid" >/dev/null || true
done
api POST "/accounts/$HUSER/cron-jobs" "$(cron_body "$INSTALL_CMD")" >/dev/null
JOB_UID="$(api GET "/accounts/$HUSER/cron-jobs" | cron_items | python3 -I -c "import sys,json; print(next(c['uid'] for c in json.load(sys.stdin) if 'install.sh' in c['command']))")"

OK=0; OUT=""
for i in $(seq 1 12); do
  sleep 15
  OUT="$(api GET "/accounts/$HUSER/cron-jobs/$JOB_UID/output" | json_get "d.get('output','')" || true)"
  if echo "$OUT" | grep -q INSTALL_OK; then OK=1; break; fi
  echo "    waiting for the cron job to run... ($i/12)"
done
api DELETE "/accounts/$HUSER/cron-jobs/$JOB_UID" >/dev/null || true
if [ "$OK" != 1 ]; then
  echo "Install task did not report success. Last output:" >&2
  echo "${OUT:-<none>}" | tail -n 25 >&2
  exit 1
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
