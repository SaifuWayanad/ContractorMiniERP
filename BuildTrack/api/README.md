# BuildTrack API (Laravel)

Laravel replacement for the ASP.NET Core backend in `../backend/BuildTrack.Api`. It serves the same
routes and JSON shapes (camelCase, `{ items, total, page, pageSize }` paging, bearer tokens), so the
existing Laravel frontend can call it by changing `BACKEND_API_BASE_URL`.

## Routes

| Area | Routes |
|------|--------|
| Auth | `POST auth/login`, `GET auth/me`, `POST auth/logout`, `POST auth/change-password` |
| Master data | `customers`, `locations`, `products` (CRUD; delete archives or deactivates) |
| Projects | `projects` (CRUD), `projects/{id}/summary` |
| Transactions | `purchases`, `expenses`, `payments` (CRUD; payments are voided, not deleted) |
| Reports | `dashboard`, `reports/outstanding`, `reports/outstanding/{id}/transactions` |
| Admin only | `users` (CRUD), `users/contractors` |
| Other | `customers/{id}/projects` |

Tokens are Laravel Sanctum tokens that expire after 8 hours, like the JWT in the .NET API.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan buildtrack:import-legacy ../backend/BuildTrack.Api/buildtrack.db --force
php artisan serve --port=8001
```

`buildtrack:import-legacy` copies the data from the ASP.NET SQLite file, keeping IDs. Without
`--force` it refuses to run when users already exist. Password hashes are kept; `$2a$` prefixes are
rewritten to `$2y$` (same bcrypt algorithm) so Laravel can verify them.

For a fresh database with no legacy data, run `php artisan db:seed`. It creates an `admin` user with
`ADMIN_PASSWORD`, or a random password it prints once.

## Tests

```bash
php artisan test
```

## Deploying to Hostinger

1. Create the MySQL database and user in hPanel, then set the `DB_*` values and `DB_CONNECTION=mysql`
   in the server's `.env`. Set `APP_ENV=production`, `APP_DEBUG=false`, and a generated `APP_KEY`.
2. Point the subdomain's document root at `api/public`.
3. Upload the `api` folder (without `vendor/`, `.env`, and `database/database.sqlite`), then run
   `composer install --no-dev --optimize-autoloader` on the server.
4. Run `php artisan migrate --force`, then the import with the legacy file (or `db:seed` for a new
   install), then `php artisan config:cache route:cache`.

The default legacy admin password is `admin123`. Change it before the API is public.
