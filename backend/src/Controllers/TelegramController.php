<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Services\PaymentService;

/**
 * Telegram Controller
 * Handles Telegram webhook
 */
class TelegramController
{
    /**
     * POST /api/telegram/webhook
     * Handle Telegram webhook updates
     */
    public function webhook(): void
    {
        $app = Application::getInstance();
        $webhookSecret = $app->getConfig('telegram.webhook_secret');

        // Verify webhook secret if configured
        if ($webhookSecret) {
            $headerSecret = Request::header('X-Telegram-Bot-Api-Secret-Token');
            if ($headerSecret !== $webhookSecret) {
                Response::unauthorized('Invalid webhook secret');
                return;
            }
        }

        $update = Request::json();

        if (empty($update)) {
            Response::error('Empty update', 400);
            return;
        }

        // Log update for debugging
        error_log('Telegram update: ' . json_encode($update));

        // Handle different update types
        if (isset($update['pre_checkout_query'])) {
            $this->handlePreCheckoutQuery($update['pre_checkout_query']);
            return;
        }

        if (isset($update['message']['successful_payment'])) {
            $this->handleSuccessfulPayment($update['message']);
            return;
        }

        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
            return;
        }

        Response::success(['handled' => false]);
    }

    /**
     * Handle pre-checkout query (payment confirmation)
     */
    private function handlePreCheckoutQuery(array $query): void
    {
        $app = Application::getInstance();
        $botToken = $app->getConfig('telegram.bot_token');

        // Always accept pre-checkout (validation was done during invoice creation)
        $response = $this->callTelegramAPI($botToken, 'answerPreCheckoutQuery', [
            'pre_checkout_query_id' => $query['id'],
            'ok' => true,
        ]);

        Response::success(['handled' => true, 'type' => 'pre_checkout']);
    }

    /**
     * Handle successful payment
     */
    private function handleSuccessfulPayment(array $message): void
    {
        $payment = $message['successful_payment'];

        $paymentService = new PaymentService();
        $result = $paymentService->processSuccessfulPayment($payment);

        if (isset($result['error'])) {
            error_log('Payment processing error: ' . $result['error']);
        }

        // Send confirmation message to user
        if (isset($result['booking_reference'])) {
            $this->sendPaymentConfirmation($message['chat']['id'], $result['booking_reference']);
        }

        Response::success(['handled' => true, 'type' => 'payment']);
    }

    /**
     * Handle regular message
     */
    private function handleMessage(array $message): void
    {
        $text = $message['text'] ?? '';
        $chatId = $message['chat']['id'];

        // Handle /start command
        if (str_starts_with($text, '/start')) {
            $this->handleStartCommand($chatId, $text);
            return;
        }

        Response::success(['handled' => false]);
    }

    /**
     * Handle /start command
     */
    private function handleStartCommand(int $chatId, string $text): void
    {
        $app = Application::getInstance();
        $botToken = $app->getConfig('telegram.bot_token');
        $appUrl = $app->getConfig('url');

        // Extract start parameter
        $parts = explode(' ', $text);
        $startParam = $parts[1] ?? '';

        // Create Mini App button
        $keyboard = [
            'inline_keyboard' => [[
                [
                    'text' => '🚤 Open Phuket Yachts',
                    'web_app' => ['url' => $appUrl],
                ],
            ]],
        ];

        $message = "🌴 Welcome to **Phuket Yacht & Tours**!\n\n";
        $message .= "Discover luxury yachts, speedboats, and amazing island tours.\n\n";
        $message .= "✨ **Features:**\n";
        $message .= "• Book yachts & tours instantly\n";
        $message .= "• Earn 5% cashback on every booking\n";
        $message .= "• Invite friends & earn rewards\n";
        $message .= "• Pay with Telegram Stars\n\n";
        $message .= "Tap the button below to start exploring! 👇";

        $this->callTelegramAPI($botToken, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
            'reply_markup' => $keyboard,
        ]);

        Response::success(['handled' => true]);
    }

    /**
     * Send payment confirmation
     */
    private function sendPaymentConfirmation(int $chatId, string $bookingReference): void
    {
        $app = Application::getInstance();
        $botToken = $app->getConfig('telegram.bot_token');
        $appUrl = $app->getConfig('url');

        $message = "✅ **Payment Successful!**\n\n";
        $message .= "Booking Reference: `{$bookingReference}`\n\n";
        $message .= "Thank you for your booking! You will receive a confirmation shortly.\n\n";
        $message .= "🎁 Cashback has been credited to your account!";

        $keyboard = [
            'inline_keyboard' => [[
                [
                    'text' => '📋 View Booking',
                    'web_app' => ['url' => "{$appUrl}/bookings/{$bookingReference}"],
                ],
            ]],
        ];

        $this->callTelegramAPI($botToken, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
            'reply_markup' => $keyboard,
        ]);
    }

    /**
     * Call Telegram Bot API
     */
    private function callTelegramAPI(string $botToken, string $method, array $data): array
    {
        $url = "https://api.telegram.org/bot{$botToken}/{$method}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }
}
