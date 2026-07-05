# Installation Guide

## Production Deployment

### Server Requirements

- Ubuntu 22.04+ or similar Linux
- 4GB RAM minimum (8GB recommended)
- 50GB SSD storage
- Docker 24+ and Docker Compose v2

### Step 1: Clone Repository

```bash
git clone https://github.com/your-org/posheh.git
cd posheh
```

### Step 2: Configure Environment

```bash
cp backend/.env.example backend/.env
```

Edit `backend/.env`:

```env
APP_NAME=Posheh
APP_ENV=production
APP_URL=https://api.yourdomain.com
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=posheh
DB_USERNAME=posheh
DB_PASSWORD=your-secure-password

REDIS_HOST=redis
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

ZARINPAL_MERCHANT_ID=your-merchant-id
ZARINPAL_SANDBOX=false

SMS_PROVIDER=kavenegar
SMS_API_KEY=your-sms-api-key
```

### Step 3: Build and Start

```bash
docker compose up -d --build
```

Database migrations run automatically when the app container starts. For demo data, seed once:

```bash
docker compose exec app php artisan db:seed --force
```

### Step 4: Storage Link

```bash
docker compose exec app php artisan storage:link
```

### Step 5: Build Frontend

```bash
cd frontend
npm ci
VITE_API_URL=https://api.yourdomain.com/api/v1 npm run build
```

### Step 6: SSL Configuration

Use a reverse proxy (Nginx/Caddy) with Let's Encrypt:

```nginx
server {
    listen 443 ssl http2;
    server_name api.yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/api.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.yourdomain.com/privkey.pem;

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### Step 7: Configure Cron (Backups)

```bash
# Add to crontab
0 2 * * * docker compose exec app php artisan backup:run
0 3 * * * docker compose exec app php artisan properties:mark-expired
```

## Troubleshooting

### Unknown database 'posheh'

This happens when the MySQL data volume was created before the `posheh` database was configured. Fix it with:

```bash
docker compose exec mysql mysql -uroot -psecret -e "CREATE DATABASE IF NOT EXISTS posheh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
docker compose exec app php artisan migrate --seed --force
```

Or recreate the MySQL volume (deletes all data):

```bash
docker compose down
docker volume rm posheh_mysql_data
docker compose up -d --build
```

## Mobile App Build

### Android

```bash
cd mobile
flutter pub get
flutter build apk --release --dart-define=API_URL=https://api.yourdomain.com/api/v1
```

### iOS / PWA

```bash
cd frontend
npm run build
# Deploy dist/ with service worker for PWA
```

### Windows Desktop

```bash
cd mobile
flutter build windows --release --dart-define=API_URL=https://api.yourdomain.com/api/v1
```

## Running Tests

```bash
cd backend
php artisan test
```

## Monitoring

- Health check: `GET /up`
- Queue monitoring: `docker compose logs -f queue`
- Application logs: `docker compose exec app php artisan pail`

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Migration fails | Check MySQL is running: `docker compose ps` |
| OTP not sending | Verify SMS_API_KEY in .env |
| 500 errors | Check logs: `docker compose logs app` |
| Permission denied | `docker compose exec app chmod -R 775 storage bootstrap/cache` |
