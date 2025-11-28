# Phuket Station - Installation Guide | Руководство по установке

## Quick Start | Быстрый старт

### One-Line Installation (Ubuntu 24.04)

```bash
curl -fsSL https://raw.githubusercontent.com/khiziresmars/pstation/main/install.sh | sudo bash
```

### Or Clone and Run

```bash
git clone https://github.com/khiziresmars/pstation.git
cd pstation
sudo chmod +x install.sh
sudo ./install.sh
```

The installer will prompt for:
- Domain name (e.g., phuket-station.com)
- Admin email for SSL certificate
- MySQL root password
- Telegram Bot token

---

## Manual Installation | Ручная установка

### Prerequisites | Предварительные требования

- Ubuntu 24.04 LTS (fresh installation recommended)
- Root or sudo access
- Domain name pointed to server IP (A record)
- At least 2GB RAM (4GB recommended)

---

### Step 1: System Packages | Системные пакеты

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y \
    software-properties-common \
    curl wget git unzip \
    nginx mysql-server redis-server \
    certbot python3-certbot-nginx \
    dnsutils
```

---

### Step 2: Install PHP 8.3 | Установка PHP 8.3

```bash
# Add PHP repository
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

# Install PHP and extensions
sudo apt install -y \
    php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip php8.3-gd \
    php8.3-intl php8.3-bcmath php8.3-redis php8.3-opcache

# Configure PHP for production
sudo sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 64M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.3/fpm/php.ini

sudo systemctl restart php8.3-fpm
```

---

### Step 3: Install Composer | Установка Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

---

### Step 4: Install Node.js 20 | Установка Node.js 20

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt install -y nodejs
sudo npm install -g pnpm
```

---

### Step 5: Setup Database | Настройка базы данных

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -e "
CREATE DATABASE phuket_station CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'phuket_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON phuket_station.* TO 'phuket_user'@'localhost';
FLUSH PRIVILEGES;
"
```

---

### Step 6: Clone Repository | Клонирование репозитория

```bash
sudo mkdir -p /var/www
sudo git clone https://github.com/khiziresmars/pstation.git /var/www/phuket-station
cd /var/www/phuket-station
```

---

### Step 7: Setup Backend | Настройка бэкенда

```bash
cd /var/www/phuket-station/backend

# Install dependencies
composer install --no-dev --optimize-autoloader

# Copy and configure environment
cp .env.example .env
nano .env  # Edit with your credentials (see Environment Variables section)

# Create storage directories
mkdir -p storage/logs storage/cache storage/uploads
chmod -R 775 storage

# Run migrations
php database/migrate.php

# Seed initial data (includes admin user and demo content)
php database/seed.php

# Set permissions
sudo chown -R www-data:www-data /var/www/phuket-station/backend
sudo chmod -R 755 /var/www/phuket-station/backend
sudo chmod -R 775 /var/www/phuket-station/backend/storage
```

---

### Step 8: Setup Frontend | Настройка фронтенда

```bash
cd /var/www/phuket-station/frontend

# Configure environment
cp .env.example .env
nano .env  # Set VITE_API_URL to https://your-domain.com/api

# Install dependencies and build
pnpm install
pnpm build

# Set permissions
sudo chown -R www-data:www-data /var/www/phuket-station/frontend
```

---

### Step 9: Setup Admin Panel | Настройка админ-панели

```bash
cd /var/www/phuket-station/admin

# Configure environment
cp .env.example .env
nano .env  # Set VITE_API_URL

# Install dependencies and build
pnpm install
pnpm build

# Set permissions
sudo chown -R www-data:www-data /var/www/phuket-station/admin
```

---

### Step 10: Configure Nginx | Настройка Nginx

```bash
sudo nano /etc/nginx/sites-available/phuket-station
```

Paste the following configuration:

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;

    # Frontend (Telegram Mini App)
    root /var/www/phuket-station/frontend/dist;
    index index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    # API Backend
    location /api {
        alias /var/www/phuket-station/backend/public;
        try_files $uri $uri/ @api;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME /var/www/phuket-station/backend/public/index.php;
            include fastcgi_params;
            fastcgi_read_timeout 300;
        }
    }

    location @api {
        rewrite ^/api/(.*)$ /api/index.php?$1 last;
    }

    # Admin Panel
    location /admin {
        alias /var/www/phuket-station/admin/dist;
        try_files $uri $uri/ /admin/index.html;
    }

    # Frontend SPA
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Static assets caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -sf /etc/nginx/sites-available/phuket-station /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

### Step 11: Install SSL Certificate | Установка SSL сертификата

```bash
sudo certbot --nginx -d your-domain.com -d www.your-domain.com --non-interactive --agree-tos -m admin@your-domain.com
```

---

### Step 12: Configure Cron Jobs | Настройка планировщика

```bash
sudo crontab -e
```

Add the following lines:

```cron
# Phuket Station Scheduled Tasks

# Update exchange rates every 6 hours
0 */6 * * * cd /var/www/phuket-station/backend && /usr/bin/php scripts/update-exchange-rates.php >> /var/log/phuket-station/exchange-rates.log 2>&1

# Send booking reminders daily at 8:00 AM
0 8 * * * cd /var/www/phuket-station/backend && /usr/bin/php scripts/send-reminders.php >> /var/log/phuket-station/reminders.log 2>&1

# Cleanup old data daily at 3:00 AM
0 3 * * * cd /var/www/phuket-station/backend && /usr/bin/php scripts/cleanup.php >> /var/log/phuket-station/cleanup.log 2>&1

# Background queue worker (runs continuously)
* * * * * cd /var/www/phuket-station/backend && /usr/bin/php scripts/queue-worker.php >> /var/log/phuket-station/queue.log 2>&1
```

Create log directory:

```bash
sudo mkdir -p /var/log/phuket-station
sudo chown www-data:www-data /var/log/phuket-station
```

---

### Step 13: Configure Telegram Bot | Настройка Telegram бота

1. **Create Bot**
   - Open [@BotFather](https://t.me/BotFather)
   - Send `/newbot` and follow instructions
   - Save the bot token

2. **Enable Features**
   - Send `/mybots` → Select your bot
   - `Bot Settings` → `Inline Mode` → Enable
   - `Bot Settings` → `Payments` → Enable (if needed)

3. **Setup Mini App**
   - `Bot Settings` → `Menu Button`
   - Set URL to `https://your-domain.com`

4. **Set Webhook**
   ```bash
   curl -X POST "https://api.telegram.org/bot<YOUR_TOKEN>/setWebhook?url=https://your-domain.com/api/telegram/webhook"
   ```

5. **Verify Webhook**
   ```bash
   curl "https://api.telegram.org/bot<YOUR_TOKEN>/getWebhookInfo"
   ```

---

## Environment Variables | Переменные окружения

### Backend (.env)

```env
# Application
APP_NAME="Phuket Station"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
ADMIN_EMAIL=admin@your-domain.com

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=phuket_station
DB_USERNAME=phuket_user
DB_PASSWORD=your_secure_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Telegram
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_WEBAPP_URL=https://your-domain.com
TELEGRAM_BOT_USERNAME=YourBotUsername

# JWT Authentication
JWT_SECRET=generate_64_char_random_string_here
JWT_EXPIRATION=86400

# Email (SMTP)
MAIL_ENABLED=true
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="Phuket Station"

# Stripe Payments (optional)
STRIPE_ENABLED=false
STRIPE_SECRET_KEY=sk_live_xxx
STRIPE_PUBLISHABLE_KEY=pk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

# Crypto Payments via NowPayments (optional)
NOWPAYMENTS_ENABLED=false
NOWPAYMENTS_API_KEY=xxx
NOWPAYMENTS_IPN_SECRET=xxx

# Thai PromptPay QR Payments (optional)
PROMPTPAY_ENABLED=false
PROMPTPAY_ACCOUNT_TYPE=phone
PROMPTPAY_ACCOUNT_ID=0812345678
PROMPTPAY_MERCHANT_NAME=Phuket Station

# YooKassa Russian Payments (optional)
YOOKASSA_ENABLED=false
YOOKASSA_SHOP_ID=xxx
YOOKASSA_SECRET_KEY=xxx
YOOKASSA_TEST_MODE=true
YOOKASSA_RETURN_URL=https://your-domain.com/payment/success

# Google OAuth (optional)
GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxx
GOOGLE_REDIRECT_URI=https://your-domain.com/auth/google/callback

# Exchange Rates API
EXCHANGE_RATE_API_KEY=your_api_key
EXCHANGE_RATE_API_URL=https://api.exchangerate-api.com/v4/latest/THB

# Business Settings
CASHBACK_PERCENT=5
REFERRAL_BONUS_THB=200
DEFAULT_CURRENCY=THB
```

### Frontend (.env)

```env
VITE_API_URL=https://your-domain.com/api
VITE_APP_NAME=Phuket Station
VITE_TELEGRAM_BOT_USERNAME=YourBotUsername
VITE_STRIPE_PUBLISHABLE_KEY=pk_live_xxx
VITE_GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
VITE_ENABLE_PROMPTPAY=false
VITE_ENABLE_YOOKASSA=false
```

### Admin Panel (.env)

```env
VITE_API_URL=https://your-domain.com/api
VITE_APP_NAME=Phuket Station Admin
```

---

## Admin Panel Access | Доступ к админ-панели

- **URL**: `https://your-domain.com/admin`
- **Default Login**:
  - Email: `admin@admin.com`
  - Password: `admin`

**IMPORTANT: Change the admin password immediately after first login!**

---

## Post-Installation Checklist | Чек-лист после установки

- [ ] SSL certificate installed and working
- [ ] Admin panel accessible at /admin
- [ ] Telegram Mini App loads correctly
- [ ] API responds at /api/health
- [ ] Database migrations completed
- [ ] Cron jobs configured
- [ ] Admin password changed
- [ ] Telegram webhook set
- [ ] Email sending tested
- [ ] Exchange rates updating
- [ ] Payment systems configured (Stripe, PromptPay, YooKassa as needed)
- [ ] Enable payment methods in Admin Panel → Settings

---

## Troubleshooting | Устранение проблем

### 502 Bad Gateway

```bash
# Check PHP-FPM status
sudo systemctl status php8.3-fpm

# Restart services
sudo systemctl restart php8.3-fpm nginx
```

### Permission Issues

```bash
sudo chown -R www-data:www-data /var/www/phuket-station
sudo chmod -R 755 /var/www/phuket-station
sudo chmod -R 775 /var/www/phuket-station/backend/storage
```

### Database Connection Failed

```bash
# Check MySQL status
sudo systemctl status mysql

# Test connection
mysql -u phuket_user -p phuket_station -e "SELECT 1"

# Check credentials in .env file
cat /var/www/phuket-station/backend/.env | grep DB_
```

### API Returns 500 Error

```bash
# Check PHP error logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/www/phuket-station/backend/storage/logs/*.log

# Enable debug mode temporarily
sudo sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' /var/www/phuket-station/backend/.env
```

### SSL Certificate Issues

```bash
# Renew certificate
sudo certbot renew --dry-run

# Check certificate status
sudo certbot certificates
```

### Redis Connection Failed

```bash
# Check Redis status
sudo systemctl status redis-server

# Test connection
redis-cli ping
```

---

## Logs Location | Расположение логов

```bash
# Nginx logs
/var/log/nginx/access.log
/var/log/nginx/error.log

# PHP-FPM logs
/var/log/php8.3-fpm.log

# Application logs
/var/www/phuket-station/backend/storage/logs/

# Cron job logs
/var/log/phuket-station/
```

---

## Updating | Обновление

```bash
cd /var/www/phuket-station

# Pull latest changes
sudo git pull origin main

# Update backend
cd backend
composer install --no-dev --optimize-autoloader
php database/migrate.php

# Update frontend
cd ../frontend
pnpm install
pnpm build

# Update admin
cd ../admin
pnpm install
pnpm build

# Set permissions
sudo chown -R www-data:www-data /var/www/phuket-station

# Restart services
sudo systemctl restart php8.3-fpm nginx
```

---

## Backup | Резервное копирование

```bash
# Backup database
mysqldump -u phuket_user -p phuket_station > backup_$(date +%Y%m%d).sql

# Backup uploads
tar -czf uploads_$(date +%Y%m%d).tar.gz /var/www/phuket-station/backend/storage/uploads

# Backup environment files
cp /var/www/phuket-station/backend/.env backend_env_backup
cp /var/www/phuket-station/frontend/.env frontend_env_backup
```

---

## Support | Поддержка

- **GitHub Issues**: [https://github.com/khiziresmars/pstation/issues](https://github.com/khiziresmars/pstation/issues)
- **Telegram**: [@phuket_station_support](https://t.me/phuket_station_support)
- **Email**: support@phuket-station.com
