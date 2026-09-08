<?php
// ============================================================
// AeroGlide — logout.php
// Log out the current user session and redirect to homepage.
// ============================================================

require_once __DIR__ . '/auth_helper.php';

auth_logout();

header('Location: index.php?msg=' . urlencode('You have been logged out successfully.'));
exit;
