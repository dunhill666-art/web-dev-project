<?php
// ============================================================
// AeroGlide — api/flight_prices.php
// Returns dynamic daily aggregate flight prices for the Cebu Pacific calendar
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../database/flight_helper.php';

$destination = trim($_GET['destination'] ?? $_GET['city'] ?? $_GET['dest'] ?? 'Mayon Volcano, Albay');
$origin      = trim($_GET['origin'] ?? 'MNL');
$startDate   = trim($_GET['start_date'] ?? $_GET['month'] ?? '');
$endDate     = trim($_GET['end_date'] ?? '');

// If only month is provided like "2026-10", compute start and end date of that month + next month
if (preg_match('/^\d{4}-\d{2}$/', $startDate)) {
    $mStart = new DateTime($startDate . '-01');
    $mEnd   = (clone $mStart)->modify('+2 months')->modify('last day of this month');
    $startDate = $mStart->format('Y-m-01');
    $endDate   = $mEnd->format('Y-m-d');
} elseif (!$startDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    $today     = new DateTime('now');
    $startDate = $today->format('Y-m-01');
    $endDate   = (clone $today)->modify('+3 months')->format('Y-m-t');
}

$result = flight_get_daily_prices($destination, $origin, $startDate, $endDate);
echo json_encode($result);
exit;
