<?php
// ============================================================
// AeroGlide — api/flights.php
// Returns flight availability, schedules & 7-day carousel data
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../database/flight_helper.php';

$destination  = trim($_GET['destination'] ?? $_GET['city'] ?? 'Mayon Volcano, Albay');
$origin       = trim($_GET['origin'] ?? 'MNL');
$selectedDate = trim($_GET['date'] ?? date('Y-m-d'));

$filters = [
    'time'        => trim($_GET['time'] ?? ''),
    'stops'       => trim($_GET['stops'] ?? 'all'),
    'price_range' => trim($_GET['price_range'] ?? ''),
    'sort'        => trim($_GET['sort'] ?? 'earliest'),
];

$result = flight_search_results($destination, $origin, $selectedDate, $filters);
echo json_encode($result);
exit;
