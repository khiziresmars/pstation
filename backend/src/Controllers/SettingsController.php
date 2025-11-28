<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\Response;

/**
 * Settings Controller
 * Handles public settings endpoints
 */
class SettingsController
{
    /**
     * GET /api/settings
     * Get public settings
     */
    public function public(): void
    {
        $db = Application::getInstance()->getDatabase();

        $settings = $db->query(
            "SELECT `key`, value, type FROM settings WHERE is_public = 1"
        );

        $result = [];
        foreach ($settings as $setting) {
            $value = $setting['value'];

            // Cast value based on type
            $result[$setting['key']] = match ($setting['type']) {
                'integer' => (int) $value,
                'float' => (float) $value,
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($value, true),
                default => $value,
            };
        }

        // Add supported languages and currencies
        $result['supported_languages'] = ['en', 'ru', 'th'];
        $result['supported_currencies'] = ['THB', 'USD', 'EUR', 'RUB'];

        Response::success($result);
    }
}
