# 🚤 Phuket Station | Станция Пхукет

[![PHP Version](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://php.net)
[![React](https://img.shields.io/badge/React-18-61dafb.svg)](https://reactjs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.5-3178c6.svg)](https://typescriptlang.org)
[![Telegram Mini App](https://img.shields.io/badge/Telegram-Mini%20App-0088cc.svg)](https://core.telegram.org/bots/webapps)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Premium yacht and boat rental platform with tour booking for Phuket island. Built as a Telegram Mini App with web expansion capability.

Премиальная платформа для аренды яхт, катеров и бронирования туров на острове Пхукет. Работает как Telegram Mini App с возможностью расширения на веб-версию.

---

## 🚀 Quick Start | Быстрый старт

### One-Line Installation (Ubuntu 24.04)

```bash
curl -fsSL https://raw.githubusercontent.com/khiziresmars/pstation/main/install.sh | sudo bash
```

Or manually:

```bash
git clone https://github.com/khiziresmars/pstation.git
cd pstation
sudo chmod +x install.sh
sudo ./install.sh
```

The installer will guide you through:
- System packages installation (PHP 8.3, MySQL 8.0, Redis, Nginx)
- Database setup and migrations
- Frontend build
- SSL certificate generation (Certbot)
- Nginx configuration
- Cron jobs for scheduled tasks

📖 **[Detailed Installation Guide](INSTALL.md)**

---

## 📸 Screenshots | Скриншоты

<p align="center">
  <img src="docs/screenshots/home.png" alt="Home" width="200"/>
  <img src="docs/screenshots/catalog.png" alt="Catalog" width="200"/>
  <img src="docs/screenshots/booking.png" alt="Booking" width="200"/>
  <img src="docs/screenshots/profile.png" alt="Profile" width="200"/>
</p>

---

## 🛥️ Features | Возможности

### EN
- **Yacht & Boat Catalog** — Filter by type, capacity, price with beautiful galleries
- **Tour Booking** — Phi Phi Islands, James Bond Island, Similan and more
- **Smart Booking System** — Date/time selection, guest count, instant pricing
- **Multi-Payment Support** — Telegram Stars, Stripe, Crypto, PromptPay (Thai QR), YooKassa (Russia)
- **Multi-currency** — THB, USD, EUR, RUB with live exchange rates
- **Multi-Auth** — Telegram, Email/Password, Google OAuth
- **Loyalty Program** — 5% cashback, referral bonuses, promo codes
- **Gift Cards** — Purchase and redeem gift certificates
- **Multilingual** — English, Russian, Thai (i18next)
- **User Profile** — Booking history, order history, settings, password change, notifications
- **Admin Panel** — Full management dashboard with payment system toggles
- **Vendor Portal** — Partner management system

### RU
- **Каталог яхт и катеров** — Фильтры по типу, вместимости, цене с галереями
- **Бронирование туров** — Острова Пхи-Пхи, Джеймс Бонд, Симиланы и другие
- **Умная система бронирования** — Выбор даты/времени, количество гостей, мгновенный расчёт
- **Мульти-платежи** — Telegram Stars, Stripe, Криптовалюта, PromptPay (Тайский QR), ЮКасса (Россия)
- **Мультивалютность** — THB, USD, EUR, RUB с актуальными курсами
- **Мульти-авторизация** — Telegram, Email/Пароль, Google OAuth
- **Программа лояльности** — 5% кэшбэк, реферальные бонусы, промокоды
- **Подарочные карты** — Покупка и использование сертификатов
- **Мультиязычность** — Английский, Русский, Тайский (i18next)
- **Профиль пользователя** — История бронирований, история заказов, настройки, смена пароля, уведомления
- **Админ-панель** — Полная панель управления с переключением платёжных систем
- **Портал партнёров** — Система управления вендорами

---

## 🔧 Tech Stack | Технологии

### Backend
- PHP 8.3+ (Clean MVC architecture)
- MySQL 8.0 with migrations system
- Redis (caching & sessions)
- REST API (169+ endpoints)
- SMTP Email Service
- Telegram Bot API integration

### Frontend
- React 18 + TypeScript (strict mode)
- Vite 5 (fast builds)
- Tailwind CSS (with Telegram theme variables)
- Telegram Web App SDK (@twa-dev/sdk v7.8.0)
- Zustand (state management)
- TanStack React Query (data fetching)
- Framer Motion (animations)
- Swiper (galleries)
- i18next (localization)

### Infrastructure
- Ubuntu 24.04 LTS
- Nginx (optimized config)
- Certbot (auto SSL)
- Cron (scheduled tasks)

---

## 📋 Requirements | Требования

- **OS**: Ubuntu 24.04 LTS
- **RAM**: 2GB minimum (4GB recommended)
- **PHP**: 8.3+
- **MySQL**: 8.0+
- **Node.js**: 20 LTS
- **Domain**: With DNS pointed to server IP

---

## 📁 Project Structure | Структура проекта

```
pstation/
├── README.md
├── INSTALL.md
├── install.sh               # Automated installer
├── backend/
│   ├── composer.json
│   ├── public/index.php     # API entry point
│   ├── src/
│   │   ├── Controllers/     # API controllers
│   │   ├── Core/            # Framework core
│   │   ├── Middleware/      # Auth, CORS, etc.
│   │   └── Services/        # Business logic
│   ├── database/
│   │   ├── migrations/      # Database migrations
│   │   ├── migrate.php      # Migration runner
│   │   └── seed.php         # Data seeder
│   ├── scripts/             # Cron scripts
│   │   ├── update-exchange-rates.php
│   │   ├── send-reminders.php
│   │   ├── cleanup.php
│   │   └── queue-worker.php
│   ├── storage/
│   │   ├── logs/
│   │   └── cache/
│   └── .env.example
├── frontend/
│   ├── package.json
│   ├── vite.config.ts
│   ├── tailwind.config.js
│   ├── src/
│   │   ├── components/      # UI components
│   │   ├── pages/           # Route pages
│   │   ├── hooks/           # Custom hooks
│   │   ├── services/        # API services
│   │   ├── store/           # Zustand stores
│   │   ├── i18n/            # Translations
│   │   └── types/           # TypeScript types
│   └── .env.example
├── admin/                   # Admin panel (React + TypeScript)
│   ├── src/
│   │   ├── components/      # Layout, UI components
│   │   ├── pages/           # Dashboard, Bookings, Settings, etc.
│   │   ├── services/        # API client
│   │   └── store/           # Auth state
│   └── .env.example
├── nginx/
│   └── site.conf            # Nginx config template
└── docs/
    ├── API.md
    └── TELEGRAM_SETUP.md
```

---

## 📡 API Documentation | API Документация

### Base URL
```
https://your-domain.com/api
```

### Main Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/telegram` | Authenticate via Telegram |
| POST | `/auth/register` | Email registration |
| POST | `/auth/login` | Email login |
| GET | `/vessels` | Get all vessels with filters |
| GET | `/vessels/{slug}` | Get vessel details |
| GET | `/tours` | Get all tours |
| GET | `/tours/{slug}` | Get tour details |
| POST | `/bookings` | Create booking |
| GET | `/bookings/{reference}` | Get booking details |
| POST | `/payments/stripe/intent` | Create Stripe payment |
| POST | `/payments/crypto/create` | Create crypto payment |
| POST | `/payments/promptpay/create` | Create PromptPay QR payment |
| POST | `/payments/yookassa/create` | Create YooKassa payment |
| GET | `/user/profile` | Get user profile |
| GET | `/user/bookings` | Get user bookings |
| GET | `/user/favorites` | Get user favorites |
| POST | `/promo/validate` | Validate promo code |
| GET | `/exchange-rates` | Get exchange rates |
| GET | `/gift-cards` | Get gift cards catalog |

### Authentication
Telegram Mini App authentication:
```
Authorization: tma {initData}
```

JWT Token authentication:
```
Authorization: Bearer {token}
```

📖 **[Full API Documentation](docs/API.md)**

---

## 🤖 Telegram Setup | Настройка Telegram

1. Create bot via [@BotFather](https://t.me/BotFather)
2. Get bot token and save it
3. Enable inline mode and payments
4. Create Mini App via Bot Settings → Menu Button
5. Set webhook URL: `https://your-domain.com/api/telegram/webhook`
6. Configure Telegram Stars payments (optional)

📖 **[Telegram Setup Guide](docs/TELEGRAM_SETUP.md)**

---

## 🌍 Environment Variables | Переменные окружения

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
DB_DATABASE=phuket_station
DB_USERNAME=phuket_user
DB_PASSWORD=your_secure_password

# Telegram
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_WEBAPP_URL=https://your-domain.com

# JWT
JWT_SECRET=your_random_64_char_string

# Email
MAIL_ENABLED=true
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@phuket-station.com
MAIL_FROM_NAME="Phuket Station"

# Payments (optional)
STRIPE_SECRET_KEY=sk_live_xxx
STRIPE_PUBLISHABLE_KEY=pk_live_xxx
NOWPAYMENTS_API_KEY=xxx

# Thai PromptPay QR
PROMPTPAY_ENABLED=false
PROMPTPAY_ACCOUNT_TYPE=phone
PROMPTPAY_ACCOUNT_ID=0812345678
PROMPTPAY_MERCHANT_NAME=Phuket Station

# YooKassa (Russian Payments)
YOOKASSA_ENABLED=false
YOOKASSA_SHOP_ID=xxx
YOOKASSA_SECRET_KEY=xxx

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

---

## 🔒 Security | Безопасность

- Telegram initData validation (HMAC-SHA256)
- JWT token authentication with expiration
- SQL injection protection (PDO prepared statements)
- XSS protection (Content Security Policy)
- CORS configuration
- Rate limiting
- HTTPS enforced
- Password hashing (bcrypt)
- Input validation and sanitization

---

## 📊 Database

29 tables including:
- `users` - User accounts with multi-auth support
- `vessels` - Yachts and boats catalog
- `tours` - Tours catalog
- `bookings` - Booking records
- `payments` - Payment transactions
- `promo_codes` - Promotional codes
- `gift_cards` - Gift certificates
- `exchange_rates` - Currency rates
- `vendors` - Partner vendors
- `notification_log` - Email/notification logs
- And more...

---

## ⏰ Scheduled Tasks | Планировщик

Automated cron jobs:
- **Every 6 hours**: Exchange rates update
- **Daily 8:00 AM**: Booking reminders
- **Daily 3:00 AM**: Cleanup (expired tokens, old logs)
- **Continuous**: Background queue worker

---

## 📄 License | Лицензия

MIT License - see [LICENSE](LICENSE) file for details.

---

## 🤝 Contributing | Участие в разработке

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing`)
5. Open Pull Request

---

## 📞 Support | Поддержка

- **Telegram**: [@phuket_station_support](https://t.me/phuket_station_support)
- **Email**: support@phuket-station.com
- **Issues**: [GitHub Issues](https://github.com/khiziresmars/pstation/issues)

---

<p align="center">
  Made with ❤️ for Phuket | Сделано с любовью для Пхукета
</p>
