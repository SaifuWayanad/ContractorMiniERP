# BuildTrack — Contractor Project & Expense Manager

A full-stack contractor management application.

- **Frontend:** Laravel 11 (PHP 8.4) — Blade templates, Bootstrap 5
- **Backend:** ASP.NET Core 8 Web API (C#) — business logic, auth, database access
- **Database:** MySQL (owned exclusively by the backend via EF Core)

---

## Architecture

```
BuildTrack/
  frontend/    # Laravel 11 web application (presentation layer only)
  backend/     # ASP.NET Core 8 Web API (business logic + database)
  README.md
```

Laravel never connects to MySQL directly. All data access and business logic lives in the ASP.NET Core backend. Laravel calls the backend REST API using its built-in HTTP client, stores the JWT token server-side in the session, and renders Blade templates.

---

## Ports (local development)

| Service         | URL                          |
|-----------------|------------------------------|
| Laravel frontend | http://localhost:8000        |
| ASP.NET Core API | http://localhost:5000        |
| Swagger UI       | http://localhost:5000/swagger |

---

## Prerequisites

- PHP 8.4 + Composer 2.x
- .NET 8 SDK
- MySQL 8.x server running
- Node.js (optional, for any future asset compilation)

---

## Backend Setup (ASP.NET Core)

### 1. Configure environment

Edit `backend/BuildTrack.Api/appsettings.Development.json`:

```json
{
  "ConnectionStrings": {
    "DefaultConnection": "Server=localhost;Database=buildtrack;User=YOUR_DB_USER;Password=YOUR_DB_PASSWORD;"
  },
  "JwtSettings": {
    "SecretKey": "your-very-long-secret-key-change-this-in-production-min32chars",
    "Issuer": "BuildTrack",
    "Audience": "BuildTrack",
    "ExpiryHours": 8
  }
}
```

> **Never commit real credentials.** Use `appsettings.Development.json` (git-ignored) or environment variables.

### 2. Create the database

```sql
CREATE DATABASE buildtrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Run EF Core migrations

```bash
cd backend/BuildTrack.Api
dotnet ef database update
```

### 4. Start the backend

```bash
cd backend/BuildTrack.Api
dotnet run
```

The API will be available at http://localhost:5000. Swagger UI at http://localhost:5000/swagger.

### First administrator account

On first startup with an empty users table, a default account is created automatically:
- **Username:** `admin`
- **Password:** `admin123`

**Change this password immediately in any non-development environment.**

---

## Frontend Setup (Laravel)

### 1. Install PHP dependencies

```bash
cd frontend
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME=BuildTrack
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Backend API URL — must match the ASP.NET Core port
BACKEND_API_BASE_URL=http://localhost:5000/api

# Session (no database needed for session — uses file driver)
SESSION_DRIVER=file
SESSION_LIFETIME=480

# No direct database connection needed for Laravel
DB_CONNECTION=sqlite
DB_DATABASE=/dev/null
```

### 3. Start the frontend

```bash
cd frontend
php artisan serve --port=8000
```

The application will be available at http://localhost:8000.

---

## Running Both Together

Open two terminal windows:

**Terminal 1 — Backend:**
```bash
cd backend/BuildTrack.Api
dotnet run
```

**Terminal 2 — Frontend:**
```bash
cd frontend
php artisan serve --port=8000
```

Then open http://localhost:8000 and log in with `admin` / `admin123`.

---

## API Endpoints (summary)

All endpoints except `/api/auth/login` require `Authorization: Bearer <token>`.

| Method | Path | Description |
|--------|------|-------------|
| POST | /api/auth/login | Authenticate |
| GET | /api/auth/me | Current user |
| POST | /api/auth/logout | Logout |
| GET | /api/dashboard | Dashboard summary |
| GET/POST | /api/customers | List / create customers |
| GET/PUT/DELETE | /api/customers/{id} | Get / update / archive |
| GET/POST | /api/locations | List / create locations |
| GET/PUT/DELETE | /api/locations/{id} | Get / update / archive |
| GET/POST | /api/projects | List / create projects |
| GET/PUT/DELETE | /api/projects/{id} | Get / update / archive |
| GET | /api/projects/{id}/summary | Financial summary |
| GET/POST | /api/products | List / create products |
| GET/PUT/DELETE | /api/products/{id} | Get / update / archive |
| GET/POST | /api/purchases | List / create purchases |
| GET/PUT/DELETE | /api/purchases/{id} | Get / update / archive |
| GET/POST | /api/expenses | List / create expenses |
| GET/PUT/DELETE | /api/expenses/{id} | Get / update / archive |
| GET/POST | /api/payments | List / create payments |
| GET/PUT/DELETE | /api/payments/{id} | Get / void / delete |
| GET | /api/reports/outstanding | Outstanding report |

Full interactive documentation available at http://localhost:5000/swagger.

---

## Testing

### Backend tests
```bash
cd backend
dotnet test
```

### Frontend checks
```bash
cd frontend
php artisan route:list    # verify all routes registered
php artisan config:cache  # check config loads cleanly
```

### Integration smoke test (backend running)
```bash
# Login
curl -s -X POST http://localhost:5000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}' | jq .

# Dashboard (replace TOKEN with value from login)
curl -s http://localhost:5000/api/dashboard \
  -H "Authorization: Bearer TOKEN" | jq .
```

---

## Security notes

- JWT tokens are stored server-side in the Laravel session, never in `localStorage` or exposed to browser JavaScript.
- Passwords are hashed with BCrypt. Plain-text passwords are never stored.
- CSRF protection is enabled on all Laravel web forms.
- All financial calculations (totals, outstanding amounts) are performed exclusively by the ASP.NET Core backend.
- The backend validates all input; the frontend validation is presentation-only.
- Do not enable wildcard CORS with credentials in production.
- Rotate the JWT secret key before deploying to production.

---

## Feature checklist

- [x] Login / logout with JWT auth
- [x] Dashboard with real metrics
- [x] Customers — CRUD, search, pagination, archive
- [x] Locations — CRUD, search, pagination, archive
- [x] Projects — CRUD, search/filter, inline customer/location creation
- [x] Products — CRUD, archive
- [x] Purchases — CRUD, per-project and global view, server-side line total
- [x] Expenses — CRUD, per-project and global view
- [x] Customer Payments / Receipts — CRUD, void support
- [x] Outstanding Report — server-calculated, filterable
- [x] Project Financial Summary tab
- [x] Responsive layout (Bootstrap 5, mobile-friendly)
- [x] Inline add customer/location/product modals
- [x] Auto-populate customer from selected project (JS)
- [x] Auto-calculate line total preview (JS, not trusted by backend)
