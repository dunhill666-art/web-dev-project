<?php
// ============================================================
// AeroGlide — includes/head.php
// Reusable HTML head section & stylesheets
// ============================================================
require_once __DIR__ . '/../auth_helper.php';

$pageTitle = $pageTitle ?? 'AeroGlide — Book Smarter, Travel Further';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ag_base_url('style.css') ?>">
