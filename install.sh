#!/bin/bash

#############################################
# Phuket Station - Installation Script
# Ubuntu 24.04 LTS Full Setup
#############################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
DOMAIN=""
DB_NAME="phuket_yachts"
DB_USER="phuket_user"
DB_PASS=""
TELEGRAM_BOT_TOKEN=""
TELEGRAM_WEBAPP_URL=""
REPO_URL="https://github.com/khiziresmars/pstation.git"
INSTALL_DIR="/var/www/phuket-station"
PHP_VERSION="8.3"
NODE_VERSION="20"

# Print colored message
print_msg() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
}

# Check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root (sudo)"
        exit 1
    fi
}

# Interactive configuration
configure() {
    print_header "Configuration"

    read -p "Enter domain name (e.g., phuket-station.com): " DOMAIN
    if [[ -z "$DOMAIN" ]]; then
        print_error "Domain is required"
        exit 1
    fi

    read -p "Enter database name [$DB_NAME]: " input
    DB_NAME="${input:-$DB_NAME}"

    read -p "Enter database user [$DB_USER]: " input
    DB_USER="${input:-$DB_USER}"

    while [[ -z "$DB_PASS" ]]; do
        read -sp "Enter database password: " DB_PASS
        echo ""
    done

    read -p "Enter Telegram Bot Token: " TELEGRAM_BOT_TOKEN

    read -p "Enter installation directory [$INSTALL_DIR]: " input
    INSTALL_DIR="${input:-$INSTALL_DIR}"

    echo ""
    print_msg "Configuration summary:"
    echo "  Domain: $DOMAIN"
    echo "  Database: $DB_NAME"
    echo "  DB User: $DB_USER"
    echo "  Install Dir: $INSTALL_DIR"
    echo ""

    read -p "Proceed with installation? (y/n): " confirm
    if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
        print_msg "Installation cancelled"
        exit 0
    fi
}

# Update system
update_system() {
    print_header "Updating System"
    apt-get update -y
    apt-get upgrade -y
    apt-get install -y software-properties-common curl wget git unzip dnsutils
}

# Install PHP
install_php() {
    print_header "Installing PHP $PHP_VERSION"

    add-apt-repository -y ppa:ondrej/php
    apt-get update -y

    apt-get install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-opcache

    # Configure PHP
    PHP_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' $PHP_INI
    sed -i 's/post_max_size = .*/post_max_size = 64M/' $PHP_INI
    sed -i 's/memory_limit = .*/memory_limit = 256M/' $PHP_INI
    sed -i 's/max_execution_time = .*/max_execution_time = 300/' $PHP_INI

    systemctl restart php${PHP_VERSION}-fpm
    systemctl enable php${PHP_VERSION}-fpm

    print_msg "PHP $PHP_VERSION installed successfully"
}

# Install Composer
install_composer() {
    print_header "Installing Composer"

    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer

    print_msg "Composer installed successfully"
}

# Install Node.js
install_nodejs() {
    print_header "Installing Node.js $NODE_VERSION"

    curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -
    apt-get install -y nodejs

    # Install pnpm (faster than npm)
    npm install -g pnpm

    print_msg "Node.js $(node --version) installed successfully"
}

# Install MySQL
install_mysql() {
    print_header "Installing MySQL"

    apt-get install -y mysql-server

    systemctl start mysql
    systemctl enable mysql

    # Secure MySQL and create database
    mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"

    print_msg "MySQL installed and configured"
}

# Install Nginx
install_nginx() {
    print_header "Installing Nginx"

    apt-get install -y nginx

    systemctl start nginx
    systemctl enable nginx

    print_msg "Nginx installed successfully"
}

# Install Redis
install_redis() {
    print_header "Installing Redis"

    apt-get install -y redis-server

    systemctl start redis-server
    systemctl enable redis-server

    print_msg "Redis installed successfully"
}

# Clone repository
clone_repo() {
    print_header "Cloning Repository"

    if [[ -d "$INSTALL_DIR" ]]; then
        print_warn "Directory $INSTALL_DIR already exists. Backing up..."
        mv "$INSTALL_DIR" "${INSTALL_DIR}.backup.$(date +%Y%m%d%H%M%S)"
    fi

    git clone "$REPO_URL" "$INSTALL_DIR"

    print_msg "Repository cloned to $INSTALL_DIR"
}

# Setup Backend
setup_backend() {
    print_header "Setting Up Backend"

    cd "$INSTALL_DIR/backend"

    # Install dependencies
    composer install --no-dev --optimize-autoloader

    # Create .env file
    cat > .env <<EOF
# Application
APP_NAME="Phuket Station"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://${DOMAIN}
API_URL=https://${DOMAIN}/api

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

# Telegram
TELEGRAM_BOT_TOKEN=${TELEGRAM_BOT_TOKEN}
TELEGRAM_WEBAPP_URL=https://${DOMAIN}

# JWT
JWT_SECRET=$(openssl rand -hex 32)

# Payments
STRIPE_SECRET_KEY=
STRIPE_PUBLISHABLE_KEY=
STRIPE_WEBHOOK_SECRET=

NOWPAYMENTS_API_KEY=
NOWPAYMENTS_IPN_SECRET=

# Google OAuth
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://${DOMAIN}/auth/google/callback

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Timezone
TIMEZONE=Asia/Bangkok
EOF

    # Create required directories
    mkdir -p "$INSTALL_DIR/backend/storage/logs"
    mkdir -p "$INSTALL_DIR/backend/storage/uploads"
    mkdir -p "$INSTALL_DIR/backend/storage/cache"
    mkdir -p "$INSTALL_DIR/backend/cache"

    # Set permissions
    chown -R www-data:www-data "$INSTALL_DIR/backend"
    chmod -R 755 "$INSTALL_DIR/backend"
    chmod -R 775 "$INSTALL_DIR/backend/storage"
    chmod -R 775 "$INSTALL_DIR/backend/cache"

    print_msg "Backend configured successfully"
}

# Setup Frontend
setup_frontend() {
    print_header "Setting Up Frontend"

    cd "$INSTALL_DIR/frontend"

    # Create .env file
    cat > .env <<EOF
VITE_API_URL=https://${DOMAIN}/api
VITE_APP_NAME=Phuket Station
VITE_TELEGRAM_BOT_USERNAME=
EOF

    # Install dependencies and build
    pnpm install
    pnpm build

    # Set permissions
    chown -R www-data:www-data "$INSTALL_DIR/frontend"
    chmod -R 755 "$INSTALL_DIR/frontend"

    print_msg "Frontend built successfully"
}

# Run database migrations
run_migrations() {
    print_header "Running Database Migrations"

    cd "$INSTALL_DIR/backend"

    # Run migrations
    php database/migrate.php

    # Seed initial data
    php database/seed.php

    print_msg "Database migrations completed"
}

# Configure Nginx
configure_nginx() {
    print_header "Configuring Nginx"

    # First create HTTP-only config (for initial setup before SSL)
    cat > /etc/nginx/sites-available/phuket-station <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};

    root ${INSTALL_DIR}/frontend/dist;
    index index.html;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/rss+xml application/atom+xml image/svg+xml;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # API routes - rewrite to backend
    location /api {
        try_files \$uri \$uri/ @backend;
    }

    location @backend {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME ${INSTALL_DIR}/backend/public/index.php;
        fastcgi_param REQUEST_URI \$request_uri;
        fastcgi_param QUERY_STRING \$query_string;
    }

    location ~ ^/api/(.*)$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME ${INSTALL_DIR}/backend/public/index.php;
        fastcgi_param REQUEST_URI \$request_uri;
        fastcgi_param QUERY_STRING \$query_string;
        fastcgi_param PATH_INFO \$1;
    }

    # Frontend SPA
    location / {
        try_files \$uri \$uri/ /index.html;
    }

    # Static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Logs
    access_log /var/log/nginx/${DOMAIN}.access.log;
    error_log /var/log/nginx/${DOMAIN}.error.log;
}
EOF

    # Enable site
    ln -sf /etc/nginx/sites-available/phuket-station /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default

    # Test and reload
    nginx -t
    systemctl reload nginx

    print_msg "Nginx configured successfully"
}

# Install SSL certificate
install_ssl() {
    print_header "Installing SSL Certificate"

    apt-get install -y certbot python3-certbot-nginx

    echo ""
    read -p "Do you want to automatically install SSL certificate now? (y/n): " install_ssl_now

    if [[ "$install_ssl_now" == "y" || "$install_ssl_now" == "Y" ]]; then
        print_msg "Installing SSL certificate for ${DOMAIN}..."

        # Check if domain resolves to this server
        SERVER_IP=$(curl -s ifconfig.me 2>/dev/null || wget -qO- ifconfig.me 2>/dev/null || echo "unknown")
        DOMAIN_IP=$(dig +short ${DOMAIN} 2>/dev/null | head -1)

        if [[ "$SERVER_IP" != "$DOMAIN_IP" && -n "$DOMAIN_IP" ]]; then
            print_warn "Domain ${DOMAIN} resolves to ${DOMAIN_IP}, but this server IP is ${SERVER_IP}"
            print_warn "Make sure DNS is properly configured before proceeding"
            read -p "Continue anyway? (y/n): " continue_ssl
            if [[ "$continue_ssl" != "y" && "$continue_ssl" != "Y" ]]; then
                print_warn "SSL installation skipped. Run manually later:"
                echo "  sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
                return
            fi
        fi

        # Run certbot with --non-interactive for automation, but allow user input for email
        read -p "Enter email for SSL certificate notifications: " SSL_EMAIL

        if [[ -n "$SSL_EMAIL" ]]; then
            certbot --nginx -d ${DOMAIN} -d www.${DOMAIN} \
                --non-interactive \
                --agree-tos \
                --email ${SSL_EMAIL} \
                --redirect

            if [[ $? -eq 0 ]]; then
                print_msg "SSL certificate installed successfully!"

                # Setup auto-renewal cron
                echo "0 0 1 * * root certbot renew --quiet" >> /etc/cron.d/phuket-station
                print_msg "SSL auto-renewal configured (monthly check)"
            else
                print_error "SSL certificate installation failed"
                print_warn "You can try manually later:"
                echo "  sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
            fi
        else
            certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}
        fi
    else
        print_warn "SSL installation skipped. Run manually after DNS is configured:"
        echo "  sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
    fi
}

# Setup Cron jobs
setup_cron() {
    print_header "Setting Up Cron Jobs"

    # Create cron file
    cat > /etc/cron.d/phuket-station <<EOF
# Exchange rates update (every 6 hours)
0 */6 * * * www-data cd ${INSTALL_DIR}/backend && php scripts/update-exchange-rates.php >> /var/log/phuket-station/cron.log 2>&1

# Send booking reminders (every hour)
0 * * * * www-data cd ${INSTALL_DIR}/backend && php scripts/send-reminders.php >> /var/log/phuket-station/cron.log 2>&1

# Clean expired tokens (daily at 3am)
0 3 * * * www-data cd ${INSTALL_DIR}/backend && php scripts/cleanup.php >> /var/log/phuket-station/cron.log 2>&1

# Backup database (daily at 2am)
0 2 * * * root mysqldump -u ${DB_USER} -p${DB_PASS} ${DB_NAME} | gzip > /var/backups/phuket-station/db-\$(date +\%Y\%m\%d).sql.gz
EOF

    # Create log directory
    mkdir -p /var/log/phuket-station
    chown www-data:www-data /var/log/phuket-station

    # Create backup directory
    mkdir -p /var/backups/phuket-station

    print_msg "Cron jobs configured"
}

# Setup Firewall
setup_firewall() {
    print_header "Configuring Firewall"

    apt-get install -y ufw

    ufw default deny incoming
    ufw default allow outgoing
    ufw allow ssh
    ufw allow 'Nginx Full'

    echo "y" | ufw enable

    print_msg "Firewall configured"
}

# Create systemd service for queue worker (optional)
setup_queue_worker() {
    print_header "Setting Up Queue Worker"

    cat > /etc/systemd/system/phuket-queue.service <<EOF
[Unit]
Description=Phuket Yachts Queue Worker
After=network.target mysql.service redis.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=${INSTALL_DIR}/backend
ExecStart=/usr/bin/php scripts/queue-worker.php

[Install]
WantedBy=multi-user.target
EOF

    systemctl daemon-reload
    # systemctl enable phuket-queue
    # systemctl start phuket-queue

    print_msg "Queue worker service created (not started)"
}

# Print completion message
print_completion() {
    print_header "Installation Complete!"

    echo -e "${GREEN}Phuket Station has been installed successfully!${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Configure DNS to point ${DOMAIN} to this server"
    echo "  2. Run: sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
    echo "  3. Update Telegram Bot webhook: https://${DOMAIN}/api/telegram/webhook"
    echo "  4. Configure payment providers in: ${INSTALL_DIR}/backend/.env"
    echo ""
    echo "Important paths:"
    echo "  Frontend: ${INSTALL_DIR}/frontend/dist"
    echo "  Backend:  ${INSTALL_DIR}/backend"
    echo "  Logs:     /var/log/nginx/${DOMAIN}.*.log"
    echo "  Backups:  /var/backups/phuket-station/"
    echo ""
    echo "Admin panel:"
    echo "  URL:      https://${DOMAIN}/admin"
    echo "  Email:    admin@admin.com"
    echo "  Password: admin"
    echo ""
    echo -e "${YELLOW}IMPORTANT: Change the admin password after first login!${NC}"
    echo ""
}

# Main installation function
main() {
    print_header "Phuket Station - Installation"
    echo "This script will install and configure the complete application."
    echo ""

    check_root
    configure

    update_system
    install_php
    install_composer
    install_nodejs
    install_mysql
    install_nginx
    install_redis

    clone_repo
    setup_backend
    setup_frontend
    run_migrations

    configure_nginx
    install_ssl
    setup_cron
    setup_firewall
    setup_queue_worker

    print_completion
}

# Run main function
main "$@"
