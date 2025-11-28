# Phuket Station - Installation Guide

## Quick Start (Automatic)

For Ubuntu 24.04 LTS, run the automated installation script:

```bash
# Download and run installer
curl -fsSL https://raw.githubusercontent.com/khiziresmars/pstation/main/install.sh | sudo bash

# Or clone first and run locally
git clone https://github.com/khiziresmars/pstation.git
cd pstation
sudo chmod +x install.sh
sudo ./install.sh
```

## Manual Installation

### Prerequisites

- Ubuntu 24.04 LTS
- Root or sudo access
- Domain name pointed to server IP

### 1. System Requirements

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y \
    software-properties-common \
    curl wget git unzip \
    nginx mysql-server redis-server
```

### 2. Install PHP 8.3

```bash
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y \
    php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip php8.3-gd \
    php8.3-intl php8.3-bcmath php8.3-redis
```

### 3. Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 4. Install Node.js 20

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt install -y nodejs
sudo npm install -g pnpm
```

### 5. Setup Database

```bash
sudo mysql -e "
CREATE DATABASE phuket_yachts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'phuket_user'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD';
GRANT ALL PRIVILEGES ON phuket_yachts.* TO 'phuket_user'@'localhost';
FLUSH PRIVILEGES;
"
```

### 6. Clone Repository

```bash
sudo git clone https://github.com/khiziresmars/pstation.git /var/www/phuket-yachts
cd /var/www/phuket-yachts
```

### 7. Setup Backend

```bash
cd /var/www/phuket-yachts/backend

# Install dependencies
composer install --no-dev --optimize-autoloader

# Copy and configure environment
cp .env.example .env
nano .env  # Edit database credentials and other settings

# Run migrations
php database/migrate.php

# Seed initial data
php database/seed.php

# Set permissions
sudo chown -R www-data:www-data /var/www/phuket-yachts/backend
sudo chmod -R 755 /var/www/phuket-yachts/backend
sudo chmod -R 775 /var/www/phuket-yachts/backend/storage
```

### 8. Setup Frontend

```bash
cd /var/www/phuket-yachts/frontend

# Configure environment
cp .env.example .env
nano .env  # Set API URL

# Install dependencies and build
pnpm install
pnpm build

# Set permissions
sudo chown -R www-data:www-data /var/www/phuket-yachts/frontend
```

### 9. Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/phuket-yachts
```

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;

    root /var/www/phuket-yachts/frontend/dist;
    index index.html;

    # API
    location /api {
        try_files $uri $uri/ @api;
    }

    location @api {
        rewrite ^/api/(.*)$ /api/index.php?/$1 last;
    }

    location ~ ^/api/ {
        try_files $uri $uri/ /api/index.php?$query_string;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
            fastcgi_index index.php;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME /var/www/phuket-yachts/backend/public/index.php;
        }
    }

    # SPA
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/phuket-yachts /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### 10. Install SSL

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

### 11. Configure Telegram Bot

1. Create bot via [@BotFather](https://t.me/BotFather)
2. Get bot token
3. Set webhook:
   ```bash
   curl -X POST "https://api.telegram.org/bot<YOUR_TOKEN>/setWebhook?url=https://your-domain.com/api/telegram/webhook"
   ```
4. Configure Mini App in BotFather:
   - `/mybots` → Select bot → `Bot Settings` → `Menu Button` → Set URL to `https://your-domain.com`

## Environment Variables

### Backend (.env)

```env
# Application
APP_NAME="Phuket Station"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=phuket_yachts
DB_USERNAME=phuket_user
DB_PASSWORD=your_password

# Telegram
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_WEBAPP_URL=https://your-domain.com

# JWT
JWT_SECRET=generate_random_string_here

# Payments (optional)
STRIPE_SECRET_KEY=sk_live_xxx
STRIPE_PUBLISHABLE_KEY=pk_live_xxx
NOWPAYMENTS_API_KEY=xxx

# Google OAuth (optional)
GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxx
```

### Frontend (.env)

```env
VITE_API_URL=https://your-domain.com/api
VITE_APP_NAME=Phuket Station
VITE_TELEGRAM_BOT_USERNAME=YourBotUsername
```

## Admin Panel

- URL: `https://your-domain.com/admin`
- Default credentials:
  - Email: `admin@admin.com`
  - Password: `admin`

**IMPORTANT: Change the admin password after first login!**

## Troubleshooting

### 502 Bad Gateway
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

### Permission issues
```bash
sudo chown -R www-data:www-data /var/www/phuket-yachts
sudo chmod -R 755 /var/www/phuket-yachts
```

### Database connection errors
```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -u phuket_user -p phuket_yachts
```

### Logs
```bash
# Nginx logs
tail -f /var/log/nginx/error.log

# PHP logs
tail -f /var/log/php8.3-fpm.log

# Application logs
tail -f /var/www/phuket-yachts/backend/storage/logs/*.log
```

## Support

For issues and support, please create an issue on GitHub:
https://github.com/khiziresmars/pstation/issues
