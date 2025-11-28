# 📡 API Documentation

Base URL: `https://your-domain.com/api`

## Authentication

All authenticated endpoints require Telegram `initData` in the Authorization header:

```
Authorization: tma {initData}
```

The `initData` is provided by Telegram Web App SDK and validated on the server.

---

## Public Endpoints

### Health Check

```http
GET /health
```

**Response:**
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "timestamp": "2024-01-15T10:30:00+07:00",
    "version": "1.0.0",
    "services": {
      "database": "ok"
    }
  }
}
```

---

## Vessels

### List Vessels

```http
GET /vessels
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| type | string | Filter by type: yacht, speedboat, catamaran, sailboat |
| min_capacity | integer | Minimum guest capacity |
| max_capacity | integer | Maximum guest capacity |
| min_price | number | Minimum daily price (THB) |
| max_price | number | Maximum daily price (THB) |
| date | string | Check availability for date (YYYY-MM-DD) |
| sort | string | Sort: popular, price_asc, price_desc, rating, newest |
| page | integer | Page number (default: 1) |
| per_page | integer | Items per page (default: 12, max: 50) |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "yacht",
      "name": "Ocean Paradise",
      "slug": "ocean-paradise",
      "short_description_en": "Luxury 80ft yacht...",
      "capacity": 20,
      "length_meters": 24.38,
      "price_per_hour_thb": 45000,
      "price_per_day_thb": 320000,
      "captain_included": true,
      "fuel_included": false,
      "thumbnail": "https://...",
      "is_featured": true,
      "rating": 4.9,
      "reviews_count": 47
    }
  ],
  "pagination": {
    "total": 8,
    "per_page": 12,
    "current_page": 1,
    "total_pages": 1,
    "has_more": false
  }
}
```

### Get Featured Vessels

```http
GET /vessels/featured?limit=4
```

### Get Vessel Details

```http
GET /vessels/{slug}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "type": "yacht",
    "name": "Ocean Paradise",
    "slug": "ocean-paradise",
    "description_en": "Full description...",
    "description_ru": "Описание на русском...",
    "capacity": 20,
    "cabins": 4,
    "length_meters": 24.38,
    "year_built": 2021,
    "manufacturer": "Sunseeker",
    "model": "Predator 80",
    "features": ["Flybridge", "Jacuzzi", ...],
    "amenities": ["Master Suite", "Full Kitchen", ...],
    "crew_info": {
      "captain": "Captain James - 15 years experience",
      "crew_size": 4,
      "chef": true,
      "hostess": true
    },
    "price_per_hour_thb": 45000,
    "price_per_day_thb": 320000,
    "price_half_day_thb": 180000,
    "min_rental_hours": 4,
    "location": "Royal Phuket Marina",
    "captain_included": true,
    "fuel_included": false,
    "fuel_policy": "Fuel consumption approximately 150L/hour",
    "images": ["https://...", "https://..."],
    "thumbnail": "https://...",
    "rating": 4.9,
    "reviews_count": 47,
    "extras": [
      {
        "id": 1,
        "name_en": "Professional Photographer",
        "price_thb": 5000,
        "price_type": "per_booking"
      }
    ]
  }
}
```

### Get Vessel Availability

```http
GET /vessels/{id}/availability?start_date=2024-03-01&end_date=2024-03-31
```

**Response:**
```json
{
  "success": true,
  "data": {
    "unavailable_dates": {
      "2024-03-15": "Booked",
      "2024-03-16": "Maintenance"
    },
    "special_prices": {
      "2024-03-20": 350000
    }
  }
}
```

### Get Vessel Reviews

```http
GET /vessels/{id}/reviews?page=1
```

---

## Tours

### List Tours

```http
GET /tours
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| category | string | Filter: islands, snorkeling, fishing, sunset, adventure, private |
| min_price | number | Minimum adult price (THB) |
| max_price | number | Maximum adult price (THB) |
| max_duration | number | Maximum duration (hours) |
| date | string | Check availability (YYYY-MM-DD) |
| sort | string | Sort: popular, price_asc, price_desc, rating, duration |
| page | integer | Page number |
| per_page | integer | Items per page |

### Get Tour Details

```http
GET /tours/{slug}
```

### Get Tour Availability

```http
GET /tours/{id}/availability?start_date=2024-03-01&end_date=2024-03-31
```

**Response:**
```json
{
  "success": true,
  "data": {
    "2024-03-15": {
      "available": true,
      "slots_remaining": 15,
      "reason": null
    },
    "2024-03-16": {
      "available": false,
      "slots_remaining": 0,
      "reason": "Fully booked"
    }
  }
}
```

---

## Bookings (Authenticated)

### Calculate Booking Price

```http
POST /bookings/calculate
Authorization: tma {initData}
```

**Request Body:**
```json
{
  "type": "vessel",
  "item_id": 1,
  "date": "2024-03-20",
  "hours": 8,
  "adults": 6,
  "children": 2,
  "extras": {
    "1": 1,
    "6": 4
  },
  "pickup": false,
  "promo_code": "WELCOME10",
  "use_cashback": 1000
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "pricing": {
      "base_price_thb": 320000,
      "extras_price_thb": 19000,
      "extras_details": [
        {"id": 1, "name": "Photographer", "quantity": 1, "price": 5000},
        {"id": 6, "name": "BBQ Package", "quantity": 4, "price": 14000}
      ],
      "pickup_fee_thb": 0,
      "subtotal_thb": 339000
    },
    "promo": {
      "code": "WELCOME10",
      "type": "percentage",
      "value": 10,
      "discount": 5000
    },
    "cashback": {
      "available": 5000,
      "max_usage": 169500,
      "to_use": 1000,
      "will_earn": 16650,
      "percent": 5
    },
    "discounts": {
      "promo": 5000,
      "cashback": 1000,
      "total": 6000
    },
    "total_thb": 333000
  }
}
```

### Create Booking

```http
POST /bookings
Authorization: tma {initData}
```

**Request Body:**
```json
{
  "type": "vessel",
  "item_id": 1,
  "date": "2024-03-20",
  "start_time": "09:00",
  "hours": 8,
  "adults": 6,
  "children": 2,
  "extras": {"1": 1},
  "promo_code": "WELCOME10",
  "use_cashback": 1000,
  "special_requests": "Please prepare champagne",
  "contact_phone": "+66812345678"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Booking created successfully",
  "data": {
    "booking_reference": "PYT-2024-0001",
    "status": "pending",
    "total_price_thb": 333000,
    "cashback_earned_thb": 16650
  }
}
```

### Get Booking Details

```http
GET /bookings/{reference}
Authorization: tma {initData}
```

### Cancel Booking

```http
POST /bookings/{reference}/cancel
Authorization: tma {initData}
```

**Request Body:**
```json
{
  "reason": "Change of plans"
}
```

---

## User Profile (Authenticated)

### Get Profile

```http
GET /user/profile
Authorization: tma {initData}
```

### Update Profile

```http
PUT /user/profile
Authorization: tma {initData}
```

**Request Body:**
```json
{
  "phone": "+66812345678",
  "email": "user@example.com",
  "language_code": "en",
  "preferred_currency": "THB"
}
```

### Get User Bookings

```http
GET /user/bookings?page=1
Authorization: tma {initData}
```

### Get Favorites

```http
GET /user/favorites
Authorization: tma {initData}
```

### Add to Favorites

```http
POST /user/favorites
Authorization: tma {initData}
```

**Request Body:**
```json
{
  "type": "vessel",
  "id": 1
}
```

### Remove from Favorites

```http
DELETE /user/favorites/{type}/{id}
Authorization: tma {initData}
```

### Get Cashback History

```http
GET /user/cashback
Authorization: tma {initData}
```

### Get Referral Stats

```http
GET /user/referrals
Authorization: tma {initData}
```

---

## Promo Codes

### Validate Promo Code

```http
POST /promo/validate
```

**Request Body:**
```json
{
  "code": "WELCOME10",
  "type": "vessel",
  "item_id": 1,
  "amount": 320000
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "code": "WELCOME10",
    "type": "percentage",
    "value": 10,
    "discount": 5000,
    "description": "10% off for first-time customers"
  }
}
```

---

## Exchange Rates

### Get All Rates

```http
GET /exchange-rates
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "currency_code": "USD",
      "currency_name": "US Dollar",
      "currency_symbol": "$",
      "rate_to_thb": 35.5,
      "rate_from_thb": 0.028169
    }
  ]
}
```

---

## Settings

### Get Public Settings

```http
GET /settings
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cashback_percent": 5,
    "referral_bonus_thb": 200,
    "min_booking_hours": 4,
    "max_booking_days_ahead": 90,
    "contact_phone": "+66 76 123 456",
    "supported_languages": ["en", "ru", "th"],
    "supported_currencies": ["THB", "USD", "EUR", "RUB"]
  }
}
```

---

## Error Responses

All errors follow this format:

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message",
    "details": {
      "field": ["Validation error message"]
    }
  }
}
```

Common error codes:
- `VALIDATION_ERROR` (422) - Invalid input data
- `UNAUTHORIZED` (401) - Missing or invalid authentication
- `FORBIDDEN` (403) - Access denied
- `NOT_FOUND` (404) - Resource not found
- `INVALID_PROMO` (400) - Invalid promo code
- `INTERNAL_ERROR` (500) - Server error
