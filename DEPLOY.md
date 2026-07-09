# Deploy Delta TV to a VPS

## Prerequisites

- Ubuntu 22.04+ VPS (or Debian 12+)
- Domain pointed to your VPS IP
- SSH access to your VPS

---

## 1. Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install essentials
sudo apt install -y nginx certbot python3-certbot-nginx git curl wget unzip

# Install PHP 8.5 (Ondrej PPA)
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.5-fpm php8.5-cli php8.5-mbstring php8.5-xml php8.5-curl \
    php8.5-bcmath php8.5-zip php8.5-intl php8.5-mysql php8.5-sqlite3 php8.5-redis

# Install Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# Install Node.js 22+
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# Install Redis
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Install yt-dlp
sudo wget https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -O /usr/local/bin/yt-dlp
sudo chmod +x /usr/local/bin/yt-dlp
```

---

## 2. Database

Choose either **MySQL** or **SQLite**.

### Option A: MySQL (recommended for production)

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE deltatv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'deltatv'@'localhost' IDENTIFIED BY 'your-strong-password';
GRANT ALL PRIVILEGES ON deltatv.* TO 'deltatv'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Option B: SQLite (simpler, fine for low traffic)

No setup needed — the app will create the file automatically.

---

## 3. Deploy the Application

```bash
# Create app directory
sudo mkdir -p /var/www/deltatv
sudo chown $USER:$USER /var/www/deltatv

# Clone the repo
git clone <your-repo-url> /var/www/deltatv

# Or copy files via rsync from local
# rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.env' \
#     . user@your-vps:/var/www/deltatv

cd /var/www/deltatv

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install and build frontend
npm ci
npm run build

# Environment config
cp .env.example .env
php artisan key:generate
```

### Edit `.env`

```env
APP_NAME="Delta TV"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (pick one)
# MySQL:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=deltatv
DB_USERNAME=deltatv
DB_PASSWORD=your-strong-password

# OR SQLite:
# DB_CONNECTION=sqlite
# DB_DATABASE=/var/www/deltatv/database/database.sqlite

# Queue
QUEUE_CONNECTION=redis

# Session
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Cache
CACHE_STORE=redis

# yt-dlp
DOWNLADER_YT_DLP_PATH=/usr/local/bin/yt-dlp
DOWNLADER_COOKIES_PATH=
DOWNLADER_RETENTION_HOURS=24
```

```bash
# Run migrations and seeders
php artisan migrate --seed

# Create storage link
php artisan storage:link

# Set permissions
sudo chown -R www-data:www-data /var/www/deltatv/storage
sudo chown -R www-data:www-data /var/www/deltatv/bootstrap/cache
```

---

## 4. Queue Worker (Supervisor)

Downloads run as queued jobs. Supervisor keeps the worker alive.

```bash
sudo apt install -y supervisor
```

Create `/etc/supervisor/conf.d/deltatv-worker.conf`:

```ini
[program:deltatv-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/deltatv/artisan queue:work redis --sleep=3 --tries=1 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/deltatv/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start deltatv-worker:*
```

---

## 5. Scheduler (Cron)

Add to crontab (`sudo crontab -e`):

```cron
* * * * * cd /var/www/deltatv && php artisan schedule:run >> /dev/null 2>&1
```

This handles cleanup of expired download files.

---

## 6. Nginx + SSL

Create `/etc/nginx/sites-available/deltatv`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    root /var/www/deltatv/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    # Security
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    # Gzip
    gzip on;
    gzip_types text/css application/javascript image/svg+xml;
    gzip_proxied any;

    # Static assets
    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location /images/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Downloads directory (not served directly — use token route)
    location /storage/ {
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 600;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/deltatv /etc/nginx/sites-enabled/
sudo nginx -t

# Get SSL certificate
sudo certbot --nginx -d your-domain.com

# Reload nginx
sudo systemctl reload nginx
```

---

## 7. File Size Tuning

yt-dlp may download large files. Increase PHP limits:

```bash
sudo nano /etc/php/8.5/fpm/php.ini
```

```ini
max_execution_time = 600
max_input_time = 600
memory_limit = 512M
post_max_size = 100M
upload_max_filesize = 100M
```

Also increase Nginx client max body size in `/etc/nginx/nginx.conf`:

```nginx
http {
    client_max_body_size 100M;
    # ...
}
```

```bash
sudo systemctl restart php8.5-fpm
sudo systemctl reload nginx
```

---

## 8. Final Steps

```bash
# Test download links
php artisan route:list --path=download

# Check that the queue worker is running
sudo supervisorctl status

# Test the download page
curl -I https://your-domain.com/download
```

Visit `https://your-domain.com` in your browser.

---

## 9. Common Issues

| Problem | Fix |
|---------|-----|
| yt-dlp "not found" | Run `which yt-dlp` and update `DOWNLADER_YT_DLP_PATH` in `.env` |
| Queue jobs not processing | Check `sudo supervisorctl status` and `/var/www/deltatv/storage/logs/worker.log` |
| 502 Bad Gateway | Restart PHP: `sudo systemctl restart php8.5-fpm` |
| 413 Request Entity Too Large | Increase `client_max_body_size` in nginx config |
| Downloads fail with "Requested format not available" | Update yt-dlp: `sudo yt-dlp -U` |
