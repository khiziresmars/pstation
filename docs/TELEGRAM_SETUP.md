# 🤖 Telegram Bot & Mini App Setup Guide

Complete guide for setting up Telegram Bot and Mini App for Phuket Yacht & Tours.

---

## 📱 Creating the Bot

### Step 1: Create Bot with BotFather

1. Open Telegram and search for [@BotFather](https://t.me/BotFather)
2. Send `/start` to begin
3. Send `/newbot` to create a new bot
4. Follow the prompts:
   - Enter bot name: `Phuket Yacht Tours`
   - Enter bot username: `phuket_yachts_bot` (must end with `bot`)
5. **Save the Bot Token** - you'll need it for configuration

### Step 2: Configure Bot Settings

In BotFather, send `/mybots` and select your bot:

#### Set Bot Description
```
/setdescription
```
Enter:
```
🌴 Premium Yacht Rentals & Island Tours in Phuket

✨ Features:
• Book luxury yachts & speedboats
• Explore Phi Phi, Similan, James Bond Island
• 5% cashback on every booking
• Pay with Telegram Stars

Book your dream adventure now! 🚤
```

#### Set About Text
```
/setabouttext
```
Enter:
```
Phuket's premier yacht rental and tour booking platform. Book luxury yachts, speedboats, and unforgettable island tours directly in Telegram!
```

#### Set Bot Commands
```
/setcommands
```
Enter:
```
start - Open Mini App
help - Get help
bookings - View my bookings
favorites - View favorites
referral - Get referral link
```

#### Set Profile Picture
```
/setuserpic
```
Upload a square image (512x512px recommended)

---

## 🖼️ Setting Up Mini App

### Step 1: Configure Menu Button

In BotFather, select your bot:
1. Go to **Bot Settings** → **Menu Button**
2. Select **Configure menu button**
3. Enter menu button text: `🚤 Book Now`
4. Enter URL: `https://your-domain.com`

### Step 2: Configure Mini App

In BotFather:
1. Go to **Bot Settings** → **Configure Mini App**
2. Enter Mini App URL: `https://your-domain.com`
3. Enter Mini App name: `Phuket Yachts`

### Step 3: Enable Inline Mode (Optional)

In BotFather:
1. Send `/setinline`
2. Select your bot
3. Enter placeholder: `Search yachts and tours...`

---

## 💳 Setting Up Payments

### Telegram Stars (Recommended)

1. In BotFather, send `/mybots`
2. Select your bot
3. Go to **Payments**
4. Enable **Telegram Stars**
5. Stars are automatically enabled for all bots

### Alternative Payment Providers

For traditional payments (credit cards), connect a provider:

1. In BotFather → **Payments**
2. Select a provider:
   - **Stripe** (recommended for global payments)
   - **YooMoney** (for Russian payments)
   - **Tranzzo** (for Ukrainian payments)
3. Follow provider-specific setup
4. Save the **Payment Token**

---

## 🔗 Webhook Configuration

### Set Webhook URL

```bash
curl -X POST "https://api.telegram.org/bot<BOT_TOKEN>/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://your-domain.com/api/telegram/webhook",
    "secret_token": "your_webhook_secret_here",
    "allowed_updates": ["message", "callback_query", "pre_checkout_query", "successful_payment"]
  }'
```

### Verify Webhook

```bash
curl "https://api.telegram.org/bot<BOT_TOKEN>/getWebhookInfo"
```

Expected response:
```json
{
  "ok": true,
  "result": {
    "url": "https://your-domain.com/api/telegram/webhook",
    "has_custom_certificate": false,
    "pending_update_count": 0,
    "last_error_date": null,
    "last_error_message": null,
    "max_connections": 40
  }
}
```

### Delete Webhook (if needed)

```bash
curl "https://api.telegram.org/bot<BOT_TOKEN>/deleteWebhook"
```

---

## ⚙️ Environment Configuration

Add to your backend `.env` file:

```env
# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrstUVWxyz
TELEGRAM_BOT_USERNAME=phuket_yachts_bot
TELEGRAM_PAYMENT_TOKEN=123456789:TEST:xxxx  # For payments (if using provider)
TELEGRAM_WEBHOOK_SECRET=your_random_secret_string_here

# Telegram Stars
TELEGRAM_STARS_ENABLED=true
TELEGRAM_STARS_RATE_THB=0.46  # Approximate THB per Star
```

Add to your frontend `.env` file:

```env
VITE_TELEGRAM_BOT_USERNAME=phuket_yachts_bot
```

---

## 🔐 Security Best Practices

### 1. Validate initData

Always validate Telegram `initData` on your server:

```php
// PHP Example
function validateInitData(string $initData, string $botToken): ?array
{
    parse_str($initData, $data);

    if (!isset($data['hash'])) {
        return null;
    }

    $hash = $data['hash'];
    unset($data['hash']);

    // Check auth_date (max 24 hours)
    if (isset($data['auth_date'])) {
        if (time() - (int)$data['auth_date'] > 86400) {
            return null;
        }
    }

    ksort($data);
    $dataCheckString = implode("\n", array_map(
        fn($k, $v) => "$k=$v",
        array_keys($data),
        array_values($data)
    ));

    $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
    $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

    if (!hash_equals($calculatedHash, $hash)) {
        return null;
    }

    return json_decode($data['user'] ?? '{}', true);
}
```

### 2. Validate Webhook Secret

```php
// In webhook handler
$headerSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
if ($headerSecret !== $expectedSecret) {
    http_response_code(401);
    exit;
}
```

### 3. Whitelist Telegram IPs (Optional)

For extra security, whitelist Telegram's IP ranges in Nginx:

```nginx
location /api/telegram/webhook {
    allow 149.154.160.0/20;
    allow 91.108.4.0/22;
    deny all;

    try_files $uri $uri/ @api;
}
```

---

## 📲 Mini App Integration

### JavaScript SDK Usage

```typescript
import WebApp from '@twa-dev/sdk';

// Initialize
WebApp.ready();
WebApp.expand();

// Get user data
const user = WebApp.initDataUnsafe.user;
console.log(user?.first_name, user?.id);

// Get auth data for API
const initData = WebApp.initData;
fetch('/api/user/profile', {
  headers: {
    'Authorization': `tma ${initData}`
  }
});

// Main button
WebApp.MainButton.text = 'Book Now';
WebApp.MainButton.onClick(() => {
  // Handle booking
});
WebApp.MainButton.show();

// Back button
WebApp.BackButton.onClick(() => {
  history.back();
});
WebApp.BackButton.show();

// Haptic feedback
WebApp.HapticFeedback.impactOccurred('medium');
WebApp.HapticFeedback.notificationOccurred('success');

// Theme
const isDark = WebApp.colorScheme === 'dark';
const bgColor = WebApp.themeParams.bg_color;

// Open links
WebApp.openLink('https://example.com');
WebApp.openTelegramLink('https://t.me/share/url?url=...');

// Close app
WebApp.close();
```

### Available Theme Parameters

```javascript
WebApp.themeParams = {
  bg_color: '#ffffff',
  text_color: '#000000',
  hint_color: '#999999',
  link_color: '#2481cc',
  button_color: '#2481cc',
  button_text_color: '#ffffff',
  secondary_bg_color: '#f0f0f0',
  header_bg_color: '#2481cc',
  accent_text_color: '#2481cc',
  section_bg_color: '#ffffff',
  section_header_text_color: '#6d7885',
  subtitle_text_color: '#6d7885',
  destructive_text_color: '#ff3b30'
}
```

---

## 💰 Payment Flow

### Creating Telegram Stars Invoice

```php
// Backend: Create invoice
$response = callTelegramAPI('sendInvoice', [
    'chat_id' => $userId,
    'title' => 'Booking ' . $reference,
    'description' => 'Yacht rental: Ocean Paradise\nDate: March 20, 2024',
    'payload' => json_encode([
        'booking_reference' => $reference,
        'amount_thb' => 320000
    ]),
    'currency' => 'XTR', // Telegram Stars
    'prices' => [
        ['label' => 'Ocean Paradise - 8 hours', 'amount' => 696000] // Stars amount
    ]
]);
```

### Handling Pre-Checkout Query

```php
// Webhook handler
if (isset($update['pre_checkout_query'])) {
    // Validate order, check availability
    callTelegramAPI('answerPreCheckoutQuery', [
        'pre_checkout_query_id' => $update['pre_checkout_query']['id'],
        'ok' => true
    ]);
}
```

### Handling Successful Payment

```php
// Webhook handler
if (isset($update['message']['successful_payment'])) {
    $payment = $update['message']['successful_payment'];
    $payload = json_decode($payment['invoice_payload'], true);

    // Update booking status
    updateBookingStatus($payload['booking_reference'], 'paid');

    // Credit cashback
    creditCashback($userId, $payload['booking_reference']);

    // Send confirmation
    sendConfirmationMessage($update['message']['chat']['id']);
}
```

---

## 🧪 Testing

### Test Bot in Development

1. Use [@BotFather](https://t.me/BotFather) to create a test bot
2. Use ngrok for local webhook testing:
   ```bash
   ngrok http 8080
   ```
3. Set webhook to ngrok URL
4. Test with your personal Telegram account

### Test Payments

For Telegram Stars:
- Stars payments work in test mode automatically
- Use small amounts for testing

For providers (Stripe, etc.):
- Use test tokens from the provider
- Use test card numbers

### Debug Mini App

1. Open Mini App in Telegram Desktop
2. Right-click → Inspect
3. View console logs and network requests

---

## 📚 Resources

- [Telegram Bot API](https://core.telegram.org/bots/api)
- [Telegram Mini Apps](https://core.telegram.org/bots/webapps)
- [Telegram Payments](https://core.telegram.org/bots/payments)
- [@twa-dev/sdk](https://github.com/AiC-ETF/twa-dev-sdk)
- [BotFather Commands](https://core.telegram.org/bots/features#botfather)
