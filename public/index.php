<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

// Normalize SCRIPT_NAME so Symfony routing resolves against root '/'
if (isset($_SERVER['SCRIPT_NAME']) && str_starts_with($_SERVER['SCRIPT_NAME'], '/public/')) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return static function (array $context) {
    // 1. Configure Trusted Proxies for Ngrok, reverse proxies, and Docker networks
    $trustedProxies = $context['TRUSTED_PROXIES'] ?? $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? '127.0.0.1,REMOTE_ADDR,private_ranges';
    if ($trustedProxies) {
        Request::setTrustedProxies(
            array_map('trim', explode(',', $trustedProxies)),
            Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PREFIX | Request::HEADER_FORWARDED
        );
    }

    // 2. Configure Trusted Hosts (permits Ngrok tunnels, localhost, and custom hostnames)
    $trustedHosts = $context['TRUSTED_HOSTS'] ?? $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false;
    if ($trustedHosts && $trustedHosts !== '^.*$') {
        Request::setTrustedHosts(array_map('trim', explode(',', $trustedHosts)));
    }

    return new Kernel($context['APP_ENV'] ?? 'dev', (bool) ($context['APP_DEBUG'] ?? true));
};
