<?php
// ============================================================
// AeroGlide — admin/index.php
// Legacy script entry point: redirects directly to unified Symfony /admin route
// ============================================================

require_once dirname(__DIR__) . '/database/config.php';
require_once dirname(__DIR__) . '/database/function.php';

$adminUrl = function_exists('ag_base_url') ? ag_base_url('admin') : '/admin';

if (php_sapi_name() === 'cli') {
    require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';
    return static function (array $context) {
        return new App\Kernel($context['APP_ENV'] ?? 'dev', (bool) ($context['APP_DEBUG'] ?? true));
    };
}

header('Location: ' . $adminUrl, true, 302);
exit;
