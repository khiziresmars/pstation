<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Application;

/**
 * Health Check Controller
 */
class HealthController
{
    public function check(): void
    {
        $app = Application::getInstance();

        // Check database connection
        $dbStatus = 'ok';
        try {
            $app->getDatabase()->query("SELECT 1");
        } catch (\Exception $e) {
            $dbStatus = 'error';
        }

        Response::success([
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'services' => [
                'database' => $dbStatus,
            ],
        ]);
    }
}
