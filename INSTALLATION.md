# 📦 Installation Guide | Руководство по установке

Complete guide for deploying Phuket Yacht & Tours on Ubuntu 24.04 LTS.

Полное руководство по развертыванию приложения на Ubuntu 24.04 LTS.

---

## 📋 Requirements / Требования

- **OS**: Ubuntu 24.04 LTS (fresh installation recommended)
- **RAM**: 2GB minimum (4GB recommended)
- **Storage**: 20GB minimum
- **Domain**: Valid domain with DNS configured
- **SSL**: Certbot will be used for free SSL certificates

---

## 🚀 Quick Start (5 minutes)

For those who want to get started quickly:

```bash
# Clone repository
git clone https://github.com/your-repo/phuket-yacht-tours.git
cd phuket-yacht-tours

# Run setup script (creates database, installs dependencies)
chmod +x setup.sh
./setup.sh

# Configure environment files
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
nano backend/.env  # Edit with your credentials

# Build frontend
cd frontend && npm run build && cd ..

# Configure Nginx
sudo cp nginx/site.conf /etc/nginx/sites-available/phuket-yachts
sudo ln -s /etc/nginx/sites-available/phuket-yachts /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## 📝 Step-by-Step Installation

### Step 1: Update System

```bash
# Update package list and upgrade existing packages
sudo apt update && sudo apt upgrade -y

# Install essential tools
sudo apt install -y curl wget git unzip software-properties-common
```

### Step 2: Install Nginx

```bash
# Install Nginx
sudo apt install -y nginx

# Start and enable Nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# Verify installation
sudo nginx -t
curl http://localhost
```

### Step 3: Install PHP 8.2

```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.2 and required extensions
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-curl \
    php8.2-gd php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath \
    php8.2-intl php8.2-readline

# Verify PHP installation
php -v

# Start and enable PHP-FPM
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm

# Verify PHP-FPM is running
sudo systemctl status php8.2-fpm
```

### Step 4: Install MySQL 8.0

```bash
# Install MySQL Server
sudo apt install -y mysql-server

# Start and enable MySQL
sudo systemctl start mysql
sudo systemctl enable mysql

# Secure MySQL installation
sudo mysql_secure_installation
# Follow the prompts:
# - Set root password: YES
# - Remove anonymous users: YES
# - Disallow root login remotely: YES
# - Remove test database: YES
# - Reload privilege tables: YES

# Log into MySQL as root
sudo mysql -u root -p
```

### Step 5: Create Database and User

```sql
-- Run these commands in MySQL console

-- Create database
CREATE DATABASE phuket_yachts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (replace 'your_secure_password' with a strong password)
CREATE USER 'phuket_user'@'localhost' IDENTIFIED BY 'your_secure_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON phuket_yachts.* TO 'phuket_user'@'localhost';
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### Step 6: Install Node.js 20 LTS

```bash
# Install Node.js using NodeSource repository
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verify installation
node -v
npm -v

# Install Yarn (optional)
sudo npm install -g yarn
```

### Step 7: Install Composer

```bash
# Download and install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Verify installation
composer --version
```

### Step 8: Clone Repository

```bash
# Navigate to web directory
cd /var/www

# Clone the repository
sudo git clone https://github.com/your-repo/phuket-yacht-tours.git
cd phuket-yacht-tours

# Set ownership
sudo chown -R www-data:www-data /var/www/phuket-yacht-tours
sudo chmod -R 755 /var/www/phuket-yacht-tours

# Allow your user to modify files
sudo usermod -aG www-data $USER
```

### Step 9: Import Database Schema and Seed Data

```bash
# Import schema
mysql -u phuket_user -p phuket_yachts < database/schema.sql

# Import seed data
mysql -u phuket_user -p phuket_yachts < database/seed.sql

# Verify tables were created
mysql -u phuket_user -p -e "USE phuket_yachts; SHOW TABLES;"
```

### Step 10: Configure Backend

```bash
cd /var/www/phuket-yacht-tours/backend

# Copy environment file
cp .env.example .env

# Edit environment file
nano .env
```

Update the `.env` file with your settings:

```env
# Database
DB_HOST=localhost
DB_NAME=phuket_yachts
DB_USER=phuket_user
DB_PASSWORD=your_secure_password

# Telegram (get from @BotFather)
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_BOT_USERNAME=your_bot_username
TELEGRAM_PAYMENT_TOKEN=your_payment_token

# JWT Secret (generate a random string)
JWT_SECRET=your_random_64_char_string_here

# App URL
APP_URL=https://your-domain.com
```

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Create storage directories
mkdir -p storage/logs storage/cache
chmod -R 775 storage
```

### Step 11: Configure Frontend

```bash
cd /var/www/phuket-yacht-tours/frontend

# Copy environment file
cp .env.example .env

# Edit environment file
nano .env
```

Update the `.env` file:

```env
VITE_API_URL=https://your-domain.com/api
VITE_TELEGRAM_BOT_USERNAME=your_bot_username
```

```bash
# Install Node.js dependencies
npm install

# Build production version
npm run build
```

### Step 12: Configure Nginx

```bash
# Edit Nginx configuration
sudo nano /etc/nginx/sites-available/phuket-yachts
```

Add the following configuration (update `your-domain.com`):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;

    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;

    # SSL certificates (will be configured by Certbot)
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    # SSL settings
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;

    # Root for frontend
    root /var/www/phuket-yacht-tours/frontend/dist;
    index index.html;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json;

    # Frontend (React SPA)
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API Backend
    location /api {
        alias /var/www/phuket-yacht-tours/backend/public;
        try_files $uri $uri/ @api;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
        }
    }

    location @api {
        rewrite /api/(.*)$ /api/index.php?/$1 last;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logs
    access_log /var/log/nginx/phuket-yachts.access.log;
    error_log /var/log/nginx/phuket-yachts.error.log;
}
```

```bash
# Enable the site
sudo ln -s /etc/nginx/sites-available/phuket-yachts /etc/nginx/sites-enabled/

# Remove default site (optional)
sudo rm /etc/nginx/sites-enabled/default

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### Step 13: Install SSL Certificate with Certbot

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Follow the prompts and agree to terms

# Test auto-renewal
sudo certbot renew --dry-run
```

### Step 14: Configure Firewall

```bash
# Install UFW if not installed
sudo apt install -y ufw

# Allow SSH, HTTP, HTTPS
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

---

## 🤖 Telegram Bot Setup

### Create Bot

1. Open Telegram and search for [@BotFather](https://t.me/BotFather)
2. Send `/newbot` command
3. Follow instructions to create your bot
4. Save the **Bot Token** - you'll need it for `.env`

### Enable Payments

1. In BotFather, send `/mybots`
2. Select your bot
3. Go to **Payments**
4. Enable **Telegram Stars** or connect a payment provider
5. Save the **Payment Token**

### Create Mini App

1. In BotFather, send `/mybots`
2. Select your bot
3. Go to **Bot Settings** → **Menu Button**
4. Set menu button URL to: `https://your-domain.com`
5. Go to **Bot Settings** → **Configure Mini App**
6. Set Mini App URL to: `https://your-domain.com`

### Set Webhook

```bash
# Set webhook URL (replace with your values)
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://your-domain.com/api/telegram/webhook", "secret_token": "your_webhook_secret"}'
```

---

## ✅ Verification

### Test API

```bash
# Health check
curl https://your-domain.com/api/health

# Get vessels
curl https://your-domain.com/api/vessels

# Get tours
curl https://your-domain.com/api/tours
```

### Test Frontend

1. Open `https://your-domain.com` in browser
2. Verify the homepage loads
3. Navigate through vessels and tours

### Test Telegram Mini App

1. Open your bot in Telegram
2. Click the menu button or send `/start`
3. Open the Mini App
4. Test navigation and booking flow

---

## 🔧 Troubleshooting

### PHP-FPM not running

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl status php8.2-fpm
```

### Permission issues

```bash
sudo chown -R www-data:www-data /var/www/phuket-yacht-tours
sudo chmod -R 755 /var/www/phuket-yacht-tours
sudo chmod -R 775 /var/www/phuket-yacht-tours/backend/storage
```

### Nginx 502 Bad Gateway

```bash
# Check PHP-FPM socket
ls -la /var/run/php/php8.2-fpm.sock

# Check PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log

# Check Nginx logs
sudo tail -f /var/log/nginx/phuket-yachts.error.log
```

### Database connection issues

```bash
# Test MySQL connection
mysql -u phuket_user -p -e "SELECT 1"

# Check MySQL is running
sudo systemctl status mysql
```

### SSL certificate issues

```bash
# Renew certificate manually
sudo certbot renew

# Check certificate expiry
sudo certbot certificates
```

---

## 🔄 Updating

```bash
cd /var/www/phuket-yacht-tours

# Pull latest changes
sudo git pull origin main

# Update backend
cd backend
composer install --no-dev --optimize-autoloader

# Update frontend
cd ../frontend
npm install
npm run build

# Restart services
sudo systemctl reload nginx
sudo systemctl restart php8.2-fpm
```

---

## 📞 Support

- **Documentation**: [docs/](./docs/)
- **Issues**: GitHub Issues
- **Email**: support@phuket-yachts.com

---

**Happy sailing! 🚤🌴**
