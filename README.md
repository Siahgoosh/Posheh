# پوشه (Posheh) — Property Filing Platform

Affordable cloud-based property filing software for Iranian real estate agencies.

## Overview

پوشه is a multi-tenant SaaS platform that enables real estate offices to manage property listings, team members, and subscriptions. Built with clean architecture principles for scalability to thousands of offices.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.4, MySQL 8.4, Redis 7 |
| Web | React 19, TypeScript, TailwindCSS 4, Shadcn-style UI |
| Mobile | Flutter (Android + iOS PWA) |
| Desktop | Flutter Desktop (Windows, macOS, Linux) |
| Infrastructure | Docker, Nginx |

## Project Structure

```
posheh/
├── backend/          # Laravel 12 API
│   ├── app/
│   │   ├── Enums/
│   │   ├── DTOs/
│   │   ├── Repositories/
│   │   ├── Services/
│   │   ├── Http/
│   │   └── Models/
│   ├── database/
│   ├── routes/
│   └── tests/
├── frontend/         # React web application
├── mobile/           # Flutter mobile app
├── desktop/          # Flutter desktop builds
├── docker/           # Docker configuration
└── docs/             # Documentation
```

## Quick Start

### Prerequisites

- Docker & Docker Compose
- Node.js 20+ (for frontend development)
- PHP 8.4 + Composer (for local backend development)
- Flutter 3.5+ (for mobile/desktop)

### Docker (Recommended)

```bash
# Clone and configure
cp backend/.env.example backend/.env

# Start all services
docker compose up -d

# Run migrations and seed
docker compose exec app php artisan migrate --seed

# Build frontend
cd frontend && npm install && npm run build
```

Access:
- API: http://localhost:8000/api/v1
- Web: http://localhost:8000

### Local Development

```bash
# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve

# Frontend
cd frontend
npm install
npm run dev
```

### Demo Accounts

| Role | Mobile | OTP (dev) |
|------|--------|-----------|
| Super Admin | 09120000000 | 123456 |
| Office Manager | 09121111111 | 123456 |
| Consultant | 09122222222 | 123456 |

## Features

- **Multi-tenant** — Complete office isolation
- **OTP Authentication** — Mobile-based login with IPPanel SMS integration
- **Property Filing** — 9 property types with detail pages and similar properties
- **Favorites** — Save and manage favorite listings (web + mobile)
- **Permission System** — Private, Team, Office, Manager-only
- **Search** — Quick search, advanced filters, **saved searches**
- **Dashboard** — Statistics, activities, tasks, expiring properties
- **Tasks** — Create, assign, and track team tasks with notifications
- **Notifications** — In-app notification center (web bell + API)
- **Team Management** — View members and invite consultants
- **Subscriptions** — Basic, Professional, Unlimited plans
- **Payments** — **Aqayepardakht**, ZarinPal, Cafe Bazaar, Internal Wallet
- **Jalali Calendar** — Persian date support throughout
- **RTL Design** — Premium Persian UI with dark/light modes
- **Cross-platform** — Web, Android, iOS, Windows (Flutter) synced via API

## SMS & Payment Configuration

```env
# IPPanel SMS (https://docs.ippanel.com)
IPPANEL_API_KEY=your-api-key
IPPANEL_FROM_NUMBER=+983000505
IPPANEL_OTP_PATTERN_CODE=your-pattern-code

# Aqayepardakht (https://aqayepardakht.ir/api/)
AQAYEPARDAKHT_PIN=your-pin
AQAYEPARDAKHT_SANDBOX=true
FRONTEND_URL=http://your-domain:8000
```

## API Documentation

See [docs/api/README.md](docs/api/README.md)

## Architecture

See [docs/architecture/README.md](docs/architecture/README.md)

## License

Proprietary — All rights reserved.
