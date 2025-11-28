# 🚤 Phuket Yacht & Tours | Станция Пхукет

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![React](https://img.shields.io/badge/React-18-61dafb.svg)](https://reactjs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.0-3178c6.svg)](https://typescriptlang.org)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Premium yacht and boat rental platform with tour booking for Phuket island. Built as a Telegram Mini App with web expansion capability.

Премиальная платформа для аренды яхт, катеров и бронирования туров на острове Пхукет. Работает как Telegram Mini App с возможностью расширения на веб-версию.

---

## 📸 Screenshots / Скриншоты

<p align="center">
  <img src="docs/screenshots/home.png" alt="Home" width="200"/>
  <img src="docs/screenshots/catalog.png" alt="Catalog" width="200"/>
  <img src="docs/screenshots/booking.png" alt="Booking" width="200"/>
  <img src="docs/screenshots/profile.png" alt="Profile" width="200"/>
</p>

---

## 🛥️ Features / Возможности

### EN
- **Yacht & Boat Catalog** — Filter by type, capacity, price with beautiful galleries
- **Tour Booking** — Phi Phi Islands, James Bond Island, Similan and more
- **Smart Booking System** — Date/time selection, guest count, instant pricing
- **Telegram Stars Payment** — Native Telegram payment integration
- **Multi-currency** — THB, USD, EUR, RUB with live exchange rates
- **Loyalty Program** — 5% cashback, referral bonuses, promo codes
- **Multilingual** — English, Russian, Thai
- **User Profile** — Booking history, favorites, cashback balance

### RU
- **Каталог яхт и катеров** — Фильтры по типу, вместимости, цене с галереями
- **Бронирование туров** — Острова Пхи-Пхи, Джеймс Бонд, Симиланы и другие
- **Умная система бронирования** — Выбор даты/времени, количество гостей, мгновенный расчёт
- **Оплата Telegram Stars** — Нативная интеграция платежей Telegram
- **Мультивалютность** — THB, USD, EUR, RUB с актуальными курсами
- **Программа лояльности** — 5% кэшбэк, реферальные бонусы, промокоды
- **Мультиязычность** — Английский, Русский, Тайский
- **Профиль пользователя** — История бронирований, избранное, баланс кэшбэка

---

## 🔧 Tech Stack / Технологии

### Backend
- PHP 8.2+ (Clean architecture with Router)
- MySQL 8.0
- REST API
- Telegram Bot API integration

### Frontend
- React 18 + TypeScript (strict mode)
- Vite 5
- Tailwind CSS
- Telegram Web App SDK (@twa-dev/sdk)
- Zustand (state management)
- React Query (data fetching)
- Swiper (galleries)

### Infrastructure
- Ubuntu 24.04 LTS
- Nginx
- Certbot (SSL)

---

## 📋 Requirements / Требования

- **OS**: Ubuntu 24.04 LTS
- **RAM**: 2GB minimum (4GB recommended)
- **PHP**: 8.2+
- **MySQL**: 8.0+
- **Node.js**: 20 LTS
- **Domain**: With SSL certificate

---

## 🚀 Quick Start / Быстрый старт

### 1. Clone repository
```bash
git clone https://github.com/your-repo/phuket-yacht-tours.git
cd phuket-yacht-tours
```

### 2. Database setup
```bash
mysql -u root -p < database/schema.sql
mysql -u root -p phuket_yachts < database/seed.sql
```

### 3. Backend setup
```bash
cd backend
cp .env.example .env
# Edit .env with your credentials
composer install
```

### 4. Frontend setup
```bash
cd frontend
cp .env.example .env
# Edit .env with your API URL
npm install
npm run build
```

### 5. Nginx configuration
```bash
sudo cp nginx/site.conf /etc/nginx/sites-available/phuket-yachts
sudo ln -s /etc/nginx/sites-available/phuket-yachts /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

📖 **[Detailed Installation Guide](INSTALLATION.md)**

---

## 📡 API Documentation / API Документация

### Base URL
```
https://your-domain.com/api
```

### Main Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/vessels` | Get all vessels with filters |
| GET | `/vessels/{id}` | Get vessel details |
| GET | `/tours` | Get all tours |
| GET | `/tours/{id}` | Get tour details |
| POST | `/bookings` | Create booking |
| GET | `/bookings/{id}` | Get booking details |
| GET | `/user/profile` | Get user profile |
| GET | `/user/bookings` | Get user bookings |
| POST | `/promo/validate` | Validate promo code |
| GET | `/exchange-rates` | Get exchange rates |

### Authentication
All authenticated endpoints require Telegram `initData` in header:
```
Authorization: tma {initData}
```

📖 **[Full API Documentation](docs/API.md)**

---

## 🤖 Telegram Setup / Настройка Telegram

1. Create bot via [@BotFather](https://t.me/BotFather)
2. Enable inline mode and payments
3. Create Mini App via Bot Settings
4. Configure webhook URL
5. Set up Telegram Stars payments

📖 **[Telegram Setup Guide](docs/TELEGRAM_SETUP.md)**

---

## 📁 Project Structure / Структура проекта

```
phuket-yacht-tours/
├── README.md
├── INSTALLATION.md
├── docker-compose.yml
├── backend/
│   ├── composer.json
│   ├── public/index.php
│   ├── src/
│   │   ├── Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Middleware/
│   ├── config/
│   └── .env.example
├── frontend/
│   ├── package.json
│   ├── vite.config.ts
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── hooks/
│   │   ├── services/
│   │   ├── store/
│   │   └── types/
│   └── .env.example
├── database/
│   ├── schema.sql
│   ├── seed.sql
│   └── migrations/
├── nginx/
│   └── site.conf
├── public/
│   └── images/
└── docs/
    ├── API.md
    └── TELEGRAM_SETUP.md
```

---

## 🌍 Environment Variables / Переменные окружения

### Backend (.env)
```env
DB_HOST=localhost
DB_NAME=phuket_yachts
DB_USER=phuket_user
DB_PASSWORD=your_password

TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_PAYMENT_TOKEN=your_payment_token

CASHBACK_PERCENT=5
REFERRAL_BONUS_THB=200
```

### Frontend (.env)
```env
VITE_API_URL=https://your-domain.com/api
VITE_TELEGRAM_BOT_USERNAME=your_bot_username
```

---

## 🔒 Security / Безопасность

- Telegram initData validation on every request
- SQL injection protection (PDO prepared statements)
- XSS protection (Content Security Policy)
- CORS configuration
- Rate limiting
- HTTPS only

---

## 📄 License / Лицензия

MIT License - see [LICENSE](LICENSE) file for details.

---

## 🤝 Contributing / Участие в разработке

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing`)
5. Open Pull Request

---

## 📞 Support / Поддержка

- **Telegram**: [@phuket_yacht_support](https://t.me/phuket_yacht_support)
- **Email**: support@phuket-yachts.com
- **Issues**: [GitHub Issues](https://github.com/your-repo/phuket-yacht-tours/issues)

---

<p align="center">
  Made with ❤️ for Phuket | Сделано с любовью для Пхукета
</p>
