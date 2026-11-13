<?php
// ============================================================
// AeroGlide — admin/symfony_dashboard.php (DEPRECATED)
// Permanently redirected to the unified Symfony admin dashboard
// ============================================================

require_once dirname(__DIR__) . '/database/config.php';
require_once dirname(__DIR__) . '/database/function.php';

$target = function_exists('ag_base_url') ? ag_base_url('admin') : '/admin';
header('Location: ' . $target, true, 301);
exit;
