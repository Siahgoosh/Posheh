# AGENTS.md

## Cursor Cloud specific instructions

This repo is a monorepo for **Posheh** (پوشه), a Persian/RTL real-estate property-filing SaaS. The core product is the **Laravel 12 API** (`backend/`) plus the **React 19 + Vite web app** (`frontend/`). `mobile/` and `desktop/` are Flutter and are optional (not set up here).

### Environment already provisioned (via update script + VM snapshot)
- PHP 8.3 (CLI) with extensions: `pdo_mysql`, `redis`, `mbstring`, `bcmath`, `gd`, `zip`, `intl`, `sqlite3`, `gmp`; Composer; Node 22.
- MySQL and Redis are installed and `systemctl enabled`. The dev database `posheh` and user `posheh`/`secret` already exist, and migrations/seed have been run.
- `backend/.env` exists (copied from `.env.example`) with `APP_KEY` generated and `DB_HOST`/`REDIS_HOST` pointed at `127.0.0.1` (the committed example uses Docker hostnames `mysql`/`redis`, which do NOT work for native local dev). `backend/.env` is gitignored.

### Starting services (do NOT put these in the update script)
- Ensure datastores are running first: `sudo service mysql start` and `sudo service redis-server start` (idempotent; `redis-cli ping` should return `PONG`). They are enabled but may not be running after a fresh boot.
- Backend API: from `backend/`, run `php artisan serve --host=0.0.0.0 --port=8000`. API base is `http://localhost:8000/api/v1`.
- Web app: from `frontend/`, run `npm run dev` (Vite on port `5173`; it proxies `/api` → `http://localhost:8000`). Use `http://localhost:5173` in the browser.
- `composer dev` (in `backend/`) runs server+queue+logs+vite together, but it also expects the frontend build tooling under `backend/`; for web dev prefer running `php artisan serve` and `frontend`'s `npm run dev` separately.

### Testing / lint / build (standard commands, see `backend/composer.json` and `frontend/package.json`)
- Backend tests: `composer test` (in `backend/`) → PHPUnit. Tests use in-memory **SQLite** (`phpunit.xml`), so they do NOT need MySQL/Redis running.
- Frontend lint: `npm run lint` (oxlint). Frontend build: `npm run build` (`tsc -b && vite build`).

### Non-obvious gotchas
- **Dev OTP is fixed at `123456`.** `SMS_PROVIDER=log` so no real SMS is sent; OTP codes are written to `backend/storage/logs/`. Seeded demo accounts: `09120000000` (super admin), `09121111111` (office manager), `09122222222` (consultant).
- OTP requests are **rate limited** (~1/minute per mobile). When testing login repeatedly, either wait or use a different demo mobile number. Each OTP is single-use — send a fresh code before every verify.
- The verify response returns the auth token at the top-level `token` field (not nested under `data`). Pass it as `Authorization: Bearer <token>`.
- If you reinstall backend dependencies or change `.env`, clear cached config with `php artisan config:clear` (the `composer test` script already does this).
