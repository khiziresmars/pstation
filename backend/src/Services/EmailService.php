<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Database;
use App\Core\Logger;

/**
 * Email Service
 * Handles email notifications via SMTP
 */
class EmailService
{
    private Database $db;
    private Logger $logger;
    private bool $enabled;
    private array $config;

    public function __construct()
    {
        $app = Application::getInstance();
        $this->db = $app->getDatabase();
        $this->logger = new Logger('email');

        $this->enabled = (bool) ($_ENV['MAIL_ENABLED'] ?? false);
        $this->config = [
            'host' => $_ENV['MAIL_HOST'] ?? 'localhost',
            'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
            'username' => $_ENV['MAIL_USERNAME'] ?? '',
            'password' => $_ENV['MAIL_PASSWORD'] ?? '',
            'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@phuket-yachts.com',
            'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Phuket Yachts & Tours',
        ];
    }

    /**
     * Send email
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = true): array
    {
        if (!$this->enabled) {
            $this->logger->info('Email disabled, not sending', ['to' => $to, 'subject' => $subject]);
            return ['success' => false, 'error' => 'Email sending is disabled'];
        }

        try {
            // Use PHP's mail function as a fallback, or implement SMTP
            $headers = $this->buildHeaders($isHtml);

            if ($isHtml) {
                $body = $this->wrapHtmlBody($body);
            }

            $result = $this->sendViaSMTP($to, $subject, $body, $headers);

            if ($result) {
                $this->logger->info('Email sent', ['to' => $to, 'subject' => $subject]);
                $this->logEmail($to, $subject, 'sent');
                return ['success' => true];
            }

            $this->logger->error('Email send failed', ['to' => $to, 'subject' => $subject]);
            $this->logEmail($to, $subject, 'failed');
            return ['success' => false, 'error' => 'Failed to send email'];

        } catch (\Throwable $e) {
            $this->logger->error('Email exception', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
            $this->logEmail($to, $subject, 'failed', $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation(array $booking): array
    {
        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$booking['user_id']]);

        if (!$user || empty($user['email'])) {
            return ['success' => false, 'error' => 'User has no email'];
        }

        $subject = "Booking Confirmation - {$booking['booking_reference']}";
        $body = $this->renderTemplate('booking_confirmation', [
            'booking' => $booking,
            'user' => $user,
        ]);

        return $this->send($user['email'], $subject, $body);
    }

    /**
     * Send booking reminder email
     */
    public function sendBookingReminder(array $booking): array
    {
        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$booking['user_id']]);

        if (!$user || empty($user['email'])) {
            return ['success' => false, 'error' => 'User has no email'];
        }

        $subject = "Reminder: Your booking tomorrow - {$booking['booking_reference']}";
        $body = $this->renderTemplate('booking_reminder', [
            'booking' => $booking,
            'user' => $user,
        ]);

        return $this->send($user['email'], $subject, $body);
    }

    /**
     * Send admin notification email
     */
    public function sendAdminNotification(string $subject, string $message): array
    {
        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? null;

        if (!$adminEmail) {
            return ['success' => false, 'error' => 'Admin email not configured'];
        }

        $body = $this->renderTemplate('admin_notification', [
            'subject' => $subject,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);

        return $this->send($adminEmail, $subject, $body);
    }

    /**
     * Build email headers
     */
    private function buildHeaders(bool $isHtml): array
    {
        $headers = [
            'From' => "{$this->config['from_name']} <{$this->config['from_address']}>",
            'Reply-To' => $this->config['from_address'],
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0',
        ];

        if ($isHtml) {
            $headers['Content-Type'] = 'text/html; charset=UTF-8';
        } else {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        }

        return $headers;
    }

    /**
     * Send email via SMTP
     */
    private function sendViaSMTP(string $to, string $subject, string $body, array $headers): bool
    {
        // Simple SMTP implementation using fsockopen
        $socket = @fsockopen(
            ($this->config['encryption'] === 'ssl' ? 'ssl://' : '') . $this->config['host'],
            $this->config['port'],
            $errno,
            $errstr,
            30
        );

        if (!$socket) {
            // Fallback to PHP mail()
            $headerStr = '';
            foreach ($headers as $key => $value) {
                $headerStr .= "{$key}: {$value}\r\n";
            }
            return mail($to, $subject, $body, $headerStr);
        }

        try {
            // Read greeting
            $this->smtpRead($socket);

            // EHLO
            $this->smtpCommand($socket, "EHLO {$this->config['host']}");

            // STARTTLS if needed
            if ($this->config['encryption'] === 'tls') {
                $this->smtpCommand($socket, "STARTTLS");
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->smtpCommand($socket, "EHLO {$this->config['host']}");
            }

            // AUTH LOGIN
            if (!empty($this->config['username'])) {
                $this->smtpCommand($socket, "AUTH LOGIN");
                $this->smtpCommand($socket, base64_encode($this->config['username']));
                $this->smtpCommand($socket, base64_encode($this->config['password']));
            }

            // MAIL FROM
            $this->smtpCommand($socket, "MAIL FROM:<{$this->config['from_address']}>");

            // RCPT TO
            $this->smtpCommand($socket, "RCPT TO:<{$to}>");

            // DATA
            $this->smtpCommand($socket, "DATA");

            // Send headers and body
            $message = "To: {$to}\r\n";
            $message .= "Subject: {$subject}\r\n";
            foreach ($headers as $key => $value) {
                $message .= "{$key}: {$value}\r\n";
            }
            $message .= "\r\n{$body}\r\n.";

            $this->smtpCommand($socket, $message);

            // QUIT
            $this->smtpCommand($socket, "QUIT");

            fclose($socket);
            return true;

        } catch (\Throwable $e) {
            if (is_resource($socket)) {
                fclose($socket);
            }
            $this->logger->error('SMTP error', ['error' => $e->getMessage()]);

            // Fallback to PHP mail()
            $headerStr = '';
            foreach ($headers as $key => $value) {
                $headerStr .= "{$key}: {$value}\r\n";
            }
            return mail($to, $subject, $body, $headerStr);
        }
    }

    /**
     * Send SMTP command and check response
     */
    private function smtpCommand($socket, string $command): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->smtpRead($socket);
    }

    /**
     * Read SMTP response
     */
    private function smtpRead($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }

    /**
     * Wrap HTML body with template
     */
    private function wrapHtmlBody(string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #0088cc, #00aaff); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #fff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; }
        .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 10px 10px; }
        .button { display: inline-block; padding: 12px 24px; background: #0088cc; color: white; text-decoration: none; border-radius: 5px; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚤 Phuket Yachts & Tours</h1>
        </div>
        <div class="content">
            {$body}
        </div>
        <div class="footer">
            <p>Phuket Yachts & Tours Co., Ltd.</p>
            <p>© 2024 All rights reserved</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render email template
     */
    private function renderTemplate(string $template, array $data): string
    {
        return match ($template) {
            'booking_confirmation' => $this->renderBookingConfirmation($data),
            'booking_reminder' => $this->renderBookingReminder($data),
            'admin_notification' => $this->renderAdminNotification($data),
            default => $data['message'] ?? '',
        };
    }

    private function renderBookingConfirmation(array $data): string
    {
        $b = $data['booking'];
        $u = $data['user'];

        return <<<HTML
<h2>Booking Confirmed!</h2>
<p>Dear {$u['first_name']},</p>
<p>Thank you for your booking. Here are the details:</p>
<div class="info-box">
    <p><strong>Reference:</strong> {$b['booking_reference']}</p>
    <p><strong>Item:</strong> {$b['item_name']}</p>
    <p><strong>Date:</strong> {$b['booking_date']}</p>
    <p><strong>Guests:</strong> {$b['adults_count']} adults, {$b['children_count']} children</p>
    <p><strong>Total:</strong> ฿{$b['total_price_thb']}</p>
</div>
<p>We look forward to seeing you!</p>
<p>Best regards,<br>Phuket Yachts & Tours Team</p>
HTML;
    }

    private function renderBookingReminder(array $data): string
    {
        $b = $data['booking'];
        $u = $data['user'];

        return <<<HTML
<h2>Reminder: Your Trip is Tomorrow!</h2>
<p>Dear {$u['first_name']},</p>
<p>This is a friendly reminder about your upcoming booking:</p>
<div class="info-box">
    <p><strong>Reference:</strong> {$b['booking_reference']}</p>
    <p><strong>Item:</strong> {$b['item_name']}</p>
    <p><strong>Date:</strong> {$b['booking_date']}</p>
    <p><strong>Time:</strong> {$b['start_time']}</p>
</div>
<p>Please arrive 15 minutes before departure time.</p>
<p>Have a wonderful trip!</p>
HTML;
    }

    private function renderAdminNotification(array $data): string
    {
        return <<<HTML
<h2>{$data['subject']}</h2>
<p>{$data['message']}</p>
<p><small>Sent at: {$data['timestamp']}</small></p>
HTML;
    }

    /**
     * Log email to database
     */
    private function logEmail(string $to, string $subject, string $status, ?string $error = null): void
    {
        $this->db->insert('notification_log', [
            'template_code' => 'email',
            'recipient_type' => 'user',
            'recipient_id' => 0,
            'channel' => 'email',
            'message' => "To: {$to}, Subject: {$subject}",
            'status' => $status,
            'error_message' => $error,
            'sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null,
        ]);
    }
}
