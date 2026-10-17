<?php
// ============================================================
// AeroGlide — admin/symfony_dashboard.php
// Symfony-powered Admin Intelligence & Analytics Dashboard
// ============================================================

require_once __DIR__ . '/guard.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Controller\AdminDashboardController;
use Symfony\Component\HttpFoundation\Request;

// 1. Establish database connection using existing PDO provider
$pdo = ag_db();

// 2. Initialize Symfony HTTP Foundation Request
$request = Request::createFromGlobals();

// 3. Dispatch to Symfony Controller
$controller = new AdminDashboardController($pdo);

if ($request->query->get('action') === 'api_stats' || $request->isXmlHttpRequest()) {
    $response = $controller->apiStats($request);
} else {
    $response = $controller->index($request);
}

// 4. Send the Symfony HTTP Response
$response->send();
