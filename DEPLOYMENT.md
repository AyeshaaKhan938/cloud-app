# VMFS Cloud — Deployment Guide

> Step-by-step instructions for running VMFS Cloud locally and deploying it to a production server.  
> Last updated: 2026-05-13

---

## Table of Contents

1. [Local Development](#1-local-development)
   - [Prerequisites](#prerequisites)
   - [Installation](#installation)
   - [Running the app](#running-the-app)
   - [Laravel Herd (macOS alternative)](#laravel-herd-macos-alternative)
2. [Production Server (Ubuntu/Debian + Nginx)](#2-production-server-ubuntudebian--nginx)
   - [Server requirements](#server-requirements)
   - [1 — Install system packages](#1--install-system-packages)
   - [2 — Create a MySQL database](#2--create-a-mysql-database)
   - [3 — Deploy application code](#3--deploy-application-code)
   - [4 — Configure environment](#4--configure-environment)
   - [5 — Run migrations & build assets](#5--run-migrations--build-assets)
   - [6 — Configure Nginx](#6--configure-nginx)
   - [7 — Configure PHP-FPM](#7--configure-php-fpm)
   - [8 — Queue worker with Supervisor](#8--queue-worker-with-supervisor)
   - [9 — SSL with Let's Encrypt](#9--ssl-with-lets-encrypt)
   - [10 — File permissions](#10--file-permissions)
   - [11 — Scheduled tasks (cron)](#11--scheduled-tasks-cron)
   - [12 — Verify the deployment](#12--verify-the-deployment)
3. [Updating an Existing Deployment](#3-updating-an-existing-deployment)
4. [Environment Variables Reference](#4-environment-variables-reference)
5. [Performance Optimizations](#5-performance-optimizations)
6. [Troubleshooting](#6-troubleshooting)

---

## 1. Local Development

### Prerequisites

| Tool | Minimum version | Install |
|---|---|---|
| PHP | 8.3 | `brew install php` |
| MySQL | 8.x | `brew install mysql` |
| Composer | 2.x | https://getcomposer.org |
| Node.js | 16.x (see `.nvmrc`) | `brew install node` or `nvm install` |
| npm | 8.x+ | bundled with Node |

> **macOS tip:** Use [Homebrew](https://brew.sh) to install all of the above in minutes.

---

### Installation

```bash
# 1. Clone the repository
git clone <repo-url> vmfs-cloud
cd vmfs-cloud

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Create your local environment file
cp .env.example .env
php artisan key:generate

# 5. Create the MySQL database
mysql -u root -e "CREATE DATABASE vms_cloud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Edit .env with your database credentials
#    DB_DATABASE=vms_cloud
#    DB_USERNAME=root
#    DB_PASSWORD=yourpassword

# 7. Run all migrations
php artisan migrate

# 8. Seed default data (test user, sample types and tags)
php artisan db:seed

# 9. Create the public storage symlink (required for product/brand images)
php artisan storage:link

# 10. Build frontend assets
npm run build
```

---

### Running the app

**All-in-one command** (recommended for development):

```bash
composer run dev
```

This starts four processes concurrently:
- `php artisan serve` — Laravel HTTP server at http://127.0.0.1:8000
- `php artisan queue:listen --tries=1 --timeout=0` — Queue worker
- `php artisan pail --timeout=0` — Real-time log viewer
- `npm run dev` — Vite HMR dev server

**Login at:** http://127.0.0.1:8000/admin  
**Email:** `test@example.com`  
**Password:** `password`

> If you use a different port or host, update `APP_URL` in `.env` accordingly.  
> Image URLs in Filament automatically adapt to the current request host in local mode via `SyncPublicDiskUrlForLocalRequests` middleware.

---

### Laravel Herd (macOS alternative)

[Laravel Herd](https://herd.laravel.com) provides zero-config PHP + Nginx on macOS with `.test` domains.

```bash
# After Herd is installed, park the project directory
cd ~/Sites   # or wherever you keep projects
herd park

# Clone the project into a parked directory
git clone <repo-url> vmfs-cloud

# The app is now available at http://vmfs-cloud.test
# Update .env:
APP_URL=http://vmfs-cloud.test
```

Run migrations, seed, and `storage:link` as above. For the queue worker, Herd Pro manages it automatically; otherwise run `php artisan queue:listen` in a separate terminal.

---

## 2. Production Server (Ubuntu/Debian + Nginx)

### Server requirements

| Resource | Minimum | Recommended |
|---|---|---|
| CPU | 1 vCPU | 2+ vCPU |
| RAM | 1 GB | 2 GB |
| Disk | 20 GB | 40 GB (media uploads grow) |
| OS | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |
| PHP | 8.3 | 8.3+ |
| MySQL | 8.0 | 8.0+ |
| Nginx | 1.18+ | latest stable |

---

### 1 — Install system packages

```bash
sudo apt update && sudo apt upgrade -y

# Add PHP 8.3 repository
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP and required extensions
sudo apt install -y \
  php8.3-fpm \
  php8.3-cli \
  php8.3-mysql \
  php8.3-mbstring \
  php8.3-xml \
  php8.3-bcmath \
  php8.3-curl \
  php8.3-zip \
  php8.3-gd \
  php8.3-intl \
  php8.3-tokenizer \
  php8.3-fileinfo

# Install Nginx and MySQL
sudo apt install -y nginx mysql-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js (via NodeSource)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

### 2 — Create a MySQL database

```bash
sudo mysql -u root
```

```sql
CREATE DATABASE vms_cloud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'vmfs'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON vms_cloud.* TO 'vmfs'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

### 3 — Deploy application code

```bash
# Create the web root directory
sudo mkdir -p /var/www/vmfs-cloud
sudo chown -R $USER:$USER /var/www/vmfs-cloud

# Clone the repository
git clone <repo-url> /var/www/vmfs-cloud
cd /var/www/vmfs-cloud

# Install PHP dependencies (production mode — no dev packages)
composer install --no-dev --optimize-autoloader

# Install Node and build assets
npm ci
npm run build
```

> After the build, the `public/build/` directory contains all compiled assets.  
> The `node_modules/` directory is no longer needed and can be removed:  
> `rm -rf node_modules`

---

### 4 — Configure environment

```bash
cp .env.example .env
php artisan key:generate
nano .env   # or vim, whatever you prefer
```

Minimum required changes from `.env.example`:

```dotenv
APP_NAME="VMFS Cloud"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vms_cloud
DB_USERNAME=vmfs
DB_PASSWORD=STRONG_PASSWORD_HERE

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

FILESYSTEM_DISK=local

# Required for Flutter kiosk API admin endpoints
LOTTERY_MANAGEMENT_API_TOKEN=generate-a-long-random-string-here

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your-mail-password
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="VMFS Cloud"
```

> Generate a secure API token:
> ```bash
> php -r "echo bin2hex(random_bytes(32));"
> ```

---

### 5 — Run migrations & build assets

```bash
cd /var/www/vmfs-cloud

# Run all database migrations
php artisan migrate --force

# Seed initial data (only on first deploy)
php artisan db:seed --force

# Create the public storage symlink
php artisan storage:link

# Cache everything for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
```

---

### 6 — Configure Nginx

Create the site configuration:

```bash
sudo nano /etc/nginx/sites-available/vmfs-cloud
```

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name yourdomain.com www.yourdomain.com;

    # Redirect HTTP to HTTPS (uncomment after SSL is set up)
    # return 301 https://$host$request_uri;

    root /var/www/vmfs-cloud/public;
    index index.php;

    # Max upload size — match PHP's upload_max_filesize (100MB for ads)
    client_max_body_size 110M;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Static assets — long cache headers
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|webp|mp4|webm)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    access_log /var/log/nginx/vmfs-cloud.access.log;
    error_log  /var/log/nginx/vmfs-cloud.error.log;
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/vmfs-cloud /etc/nginx/sites-enabled/
sudo nginx -t          # verify config is valid
sudo systemctl reload nginx
```

---

### 7 — Configure PHP-FPM

Edit the PHP-FPM pool config to handle large uploads and long Livewire requests:

```bash
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

Ensure these values are set:

```ini
user = www-data
group = www-data
listen = /var/run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6
```

Edit the PHP ini for production:

```bash
sudo nano /etc/php/8.3/fpm/php.ini
```

```ini
upload_max_filesize = 110M
post_max_size = 115M
memory_limit = 256M
max_execution_time = 120
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.3-fpm
```

---

### 8 — Queue worker with Supervisor

Install Supervisor to keep the queue worker running:

```bash
sudo apt install -y supervisor
```

Create a configuration file:

```bash
sudo nano /etc/supervisor/conf.d/vmfs-queue.conf
```

```ini
[program:vmfs-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vmfs-cloud/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/vmfs-queue.log
stopwaitsecs=3600
```

Apply and start:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start vmfs-queue:*
sudo supervisorctl status
```

---

### 9 — SSL with Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx

sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

Certbot will automatically modify your Nginx config to add HTTPS and set up auto-renewal. Verify auto-renewal works:

```bash
sudo certbot renew --dry-run
```

After SSL is active, uncomment the HTTP→HTTPS redirect in your Nginx config and reload:

```bash
sudo systemctl reload nginx
```

---

### 10 — File permissions

The web server user (`www-data`) must own the writable directories:

```bash
cd /var/www/vmfs-cloud

# Application files — readable by www-data, owned by deploy user
sudo chown -R $USER:www-data .

# Writable directories
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache

# Make sure new files get group-writable permissions
find storage bootstrap/cache -type d -exec chmod g+s {} \;
```

---

### 11 — Scheduled tasks (cron)

Laravel's scheduler runs via cron. Add a single entry for the `www-data` user:

```bash
sudo crontab -u www-data -e
```

Add this line:

```cron
* * * * * cd /var/www/vmfs-cloud && php artisan schedule:run >> /dev/null 2>&1
```

---

### 12 — Verify the deployment

```bash
# Check all services are running
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mysql
sudo supervisorctl status

# Check Laravel can connect to everything
cd /var/www/vmfs-cloud
php artisan about

# Tail the application log for errors
tail -f storage/logs/laravel.log
```

Open `https://yourdomain.com/admin` in a browser. You should see the VMFS login page.

---

## 3. Updating an Existing Deployment

Run these commands each time you deploy new code to production:

```bash
cd /var/www/vmfs-cloud

# 1. Pull latest code
git pull origin master

# 2. Install any new PHP dependencies
composer install --no-dev --optimize-autoloader

# 3. Install and build frontend (only if package.json or assets changed)
npm ci && npm run build && rm -rf node_modules

# 4. Run any new migrations
php artisan migrate --force

# 5. Clear and rebuild all caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components

# 6. Restart the queue workers to pick up code changes
sudo supervisorctl restart vmfs-queue:*
```

> **Zero-downtime tip:** Use [Laravel Maintenance Mode](https://laravel.com/docs/maintenance-mode) with a secret token during deployment:
> ```bash
> php artisan down --secret="your-bypass-token"
> # ... deploy steps ...
> php artisan up
> ```
> While in maintenance mode, visit `https://yourdomain.com/your-bypass-token` to bypass it.

---

## 4. Environment Variables Reference

| Variable | Required | Description |
|---|---|---|
| `APP_KEY` | ✅ | Generated by `php artisan key:generate`. Never share or regenerate in production. |
| `APP_ENV` | ✅ | `local` or `production`. Controls debug output and optimizations. |
| `APP_DEBUG` | ✅ | `true` locally, **always `false` in production**. |
| `APP_URL` | ✅ | Full URL including scheme. Must match the browser address exactly. Affects public disk image URLs. |
| `DB_*` | ✅ | MySQL connection (host, port, database, username, password). |
| `LOTTERY_MANAGEMENT_API_TOKEN` | ✅ | Bearer token required by all `/api/v1/admin/*` and lottery management endpoints. Must match the token configured in the Flutter app. |
| `QUEUE_CONNECTION` | ✅ | Use `database` (default). Can use `redis` for higher throughput. |
| `CACHE_STORE` | ✅ | Use `database` (default). Can use `redis` for better performance. |
| `SESSION_DRIVER` | ✅ | Use `database` (default). |
| `MAIL_*` | ⚠️ | Required if notification emails are enabled in Notification Configuration. |
| `AWS_*` | ⚠️ | Only required if switching file storage to S3/compatible (set `FILESYSTEM_DISK=s3`). |
| `REDIS_*` | ⚠️ | Only required if using Redis for cache/queue. |

---

## 5. Performance Optimizations

Apply these on any production or staging environment:

```bash
# PHP OPcache — edit /etc/php/8.3/fpm/php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0   # disable in production for max speed
```

```bash
# Laravel application caching (run after every deploy)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components

# Optimize Composer autoloader
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

**Redis (optional but recommended for production scale):**

```bash
sudo apt install -y redis-server php8.3-redis
```

Then in `.env`:
```dotenv
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

---

## 6. Troubleshooting

### Blank page or 500 error

```bash
# Check Laravel log
tail -50 /var/www/vmfs-cloud/storage/logs/laravel.log

# Check Nginx error log
sudo tail -50 /var/log/nginx/vmfs-cloud.error.log

# Ensure APP_DEBUG=true temporarily to see the error in the browser
# (remember to set it back to false)
```

### Images not loading

```bash
# Ensure the storage symlink exists
ls -la /var/www/vmfs-cloud/public/storage
# Should point to: ../storage/app/public

# If missing:
php artisan storage:link

# Check APP_URL matches the actual domain exactly
```

### Queue jobs not processing

```bash
# Check Supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart vmfs-queue:*

# Check Supervisor log
tail -50 /var/log/supervisor/vmfs-queue.log

# Run a job manually to diagnose
php artisan queue:work --once -v
```

### Migrations failing

```bash
# Check DB credentials are correct
php artisan db:show

# Run with verbose output
php artisan migrate --force -v

# If a migration was partially applied, check the migrations table
mysql -u vmfs -p vms_cloud -e "SELECT * FROM migrations ORDER BY id DESC LIMIT 10;"
```

### Permission denied errors

```bash
# Fix storage and cache permissions
sudo chown -R www-data:www-data /var/www/vmfs-cloud/storage
sudo chown -R www-data:www-data /var/www/vmfs-cloud/bootstrap/cache
sudo chmod -R 775 /var/www/vmfs-cloud/storage
sudo chmod -R 775 /var/www/vmfs-cloud/bootstrap/cache
```

### Filament panel returns 419 (CSRF token mismatch)

```bash
# Clear application caches
php artisan cache:clear
php artisan config:cache

# Ensure SESSION_DRIVER is configured correctly in .env
# and the sessions table exists
php artisan session:table  # if missing
php artisan migrate
```

### `php artisan about` shows wrong environment

```bash
# Clear cached config
php artisan config:clear
php artisan config:cache

# Verify .env is not being overridden by a cached version
cat /var/www/vmfs-cloud/.env | grep APP_ENV
```
