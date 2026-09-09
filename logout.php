<?php
// ============================================================
// AeroGlide — logout.php
// User Logout Handler
// ============================================================

require_once __DIR__ . '/database/function.php';
require_once __DIR__ . '/auth_helper.php';

auth_logout();
header('Location: ' . ag_base_url('index.php?msg=' . urlencode('You have been logged out safely.')));
exit;
