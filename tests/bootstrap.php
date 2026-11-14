<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

/*
 * manuxi/sulu-dashboard-bundle is an optional dependency ("suggest") and not part of composer.json. To run the tests
 * of the dashboard tile and PHPStan, point SULU_DASHBOARD_BUNDLE_PATH to a checkout of the bundle:
 *
 *     SULU_DASHBOARD_BUNDLE_PATH=/path/to/SuluDashboardBundle vendor/bin/phpunit
 */
$dashboardPath = getenv('SULU_DASHBOARD_BUNDLE_PATH');

if (!interface_exists('Manuxi\\SuluDashboardBundle\\Widget\\DashboardWidgetInterface') && is_string($dashboardPath) && '' !== $dashboardPath) {
    spl_autoload_register(static function (string $class) use ($dashboardPath): void {
        $prefix = 'Manuxi\\SuluDashboardBundle\\';

        if (str_starts_with($class, $prefix)) {
            $file = rtrim($dashboardPath, '/\\') . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

            if (is_file($file)) {
                require_once $file;
            }
        }
    });
}
