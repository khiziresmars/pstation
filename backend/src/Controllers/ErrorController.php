<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Request;
use App\Core\Application;

/**
 * Error Controller
 * Handles client-side error reporting
 */
class ErrorController
{
    /**
     * Report client-side error
     * POST /api/errors/report
     */
    public function report(): void
    {
        $data = Request::json();

        // Validate required fields
        if (empty($data['message'])) {
            Response::json(['success' => false, 'error' => 'Message required'], 400);
            return;
        }

        // Log error
        $errorLog = [
            'timestamp' => $data['timestamp'] ?? date('c'),
            'message' => substr($data['message'] ?? '', 0, 1000),
            'stack' => substr($data['stack'] ?? '', 0, 5000),
            'component_stack' => substr($data['componentStack'] ?? '', 0, 5000),
            'url' => substr($data['url'] ?? '', 0, 500),
            'user_agent' => substr($data['userAgent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ];

        // Log to file
        $logPath = BASE_PATH . '/storage/logs/client-errors.log';
        $logDir = dirname($logPath);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logLine = date('Y-m-d H:i:s') . ' | ' . json_encode($errorLog, JSON_UNESCAPED_SLASHES) . "\n";
        file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);

        // Optionally save to database
        try {
            $db = Application::getInstance()->getDatabase();
            $db->insert('client_errors', [
                'message' => $errorLog['message'],
                'stack' => $errorLog['stack'],
                'url' => $errorLog['url'],
                'user_agent' => $errorLog['user_agent'],
                'ip_address' => $errorLog['ip'],
            ]);
        } catch (\Exception $e) {
            // Silently fail
        }

        Response::json(['success' => true]);
    }
}
