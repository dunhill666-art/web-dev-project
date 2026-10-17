<?php
// ============================================================
// AeroGlide — database/flight_helper.php
// Dynamic flight pricing and availability database engine
// Replicating Cebu Pacific daily fare aggregate & carousel logic
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/function.php';

/**
 * Standardizes destination name and retrieves airport code + display details.
 */
function flight_resolve_route(?string $destination, ?string $origin = 'MNL'): array
{
    $dest = trim($destination ?? '');
    $orig = strtoupper(trim($origin ?? 'MNL'));
    if ($orig === '') $orig = 'MNL';

    $routes = [
        'albay' => [
            'key'         => 'albay',
            'label'       => 'Mayon Volcano, Albay',
            'city'        => 'Albay',
            'dest_code'   => 'LGP',
            'dest_name'   => 'Bicol International Airport (LGP)',
            'flight_time' => '1h 20m',
            'base_rate'   => 2200,
        ],
        'boracay' => [
            'key'         => 'boracay',
            'label'       => 'Boracay, Aklan',
            'city'        => 'Boracay',
            'dest_code'   => 'MPH',
            'dest_name'   => 'Caticlan Airport (MPH)',
            'flight_time' => '1h 05m',
            'base_rate'   => 2100,
        ],
        'coron' => [
            'key'         => 'coron',
            'label'       => 'Coron, Palawan',
            'city'        => 'Coron',
            'dest_code'   => 'USU',
            'dest_name'   => 'Busuanga Airport (USU)',
            'flight_time' => '1h 15m',
            'base_rate'   => 2800,
        ],
        'siargao' => [
            'key'         => 'siargao',
            'label'       => 'Siargao, Surigao del Norte',
            'city'        => 'Siargao',
            'dest_code'   => 'IAO',
            'dest_name'   => 'Sayak Airport (IAO)',
            'flight_time' => '2h 10m',
            'base_rate'   => 3200,
        ],
        'bukidnon' => [
            'key'         => 'bukidnon',
            'label'       => 'Bukidnon',
            'city'        => 'Bukidnon (via CDO)',
            'dest_code'   => 'CGY',
            'dest_name'   => 'Laguindingan Airport (CGY)',
            'flight_time' => '1h 35m',
            'base_rate'   => 2500,
        ],
        'chocolate' => [
            'key'         => 'chocolate',
            'label'       => 'Chocolate Hills, Bohol',
            'city'        => 'Bohol',
            'dest_code'   => 'TAG',
            'dest_name'   => 'Bohol-Panglao Airport (TAG)',
            'flight_time' => '1h 25m',
            'base_rate'   => 2200,
        ],
        'malapascua' => [
            'key'         => 'malapascua',
            'label'       => 'Malapascua Island, Cebu',
            'city'        => 'Cebu / Malapascua',
            'dest_code'   => 'CEB',
            'dest_name'   => 'Mactan-Cebu International (CEB)',
            'flight_time' => '1h 15m',
            'base_rate'   => 2100,
        ],
        'taal' => [
            'key'         => 'taal',
            'label'       => 'Taal Volcano, Batangas',
            'city'        => 'Batangas (via MNL/SFS)',
            'dest_code'   => 'MNL',
            'dest_name'   => 'Ninoy Aquino International (MNL)',
            'flight_time' => '0h 55m',
            'base_rate'   => 1800,
        ],
        'kaohsiung' => [
            'key'         => 'kaohsiung',
            'label'       => 'Kaohsiung, Taiwan',
            'city'        => 'Kaohsiung',
            'dest_code'   => 'KHH',
            'dest_name'   => 'Kaohsiung International Airport (KHH)',
            'flight_time' => '2h 15m',
            'base_rate'   => 8610,
        ],
        'dumaguete' => [
            'key'         => 'dumaguete',
            'label'       => 'Dumaguete, Negros Oriental',
            'city'        => 'Dumaguete',
            'dest_code'   => 'DGT',
            'dest_name'   => 'Sibulan Airport (DGT)',
            'flight_time' => '1h 20m',
            'base_rate'   => 2400,
        ],
        'clark' => [
            'key'         => 'clark',
            'label'       => 'Clark, Pampanga',
            'city'        => 'Clark',
            'dest_code'   => 'CRK',
            'dest_name'   => 'Clark International Airport (CRK)',
            'flight_time' => '0h 45m',
            'base_rate'   => 1900,
        ],
    ];

    $matchedKey = 'albay';
    $lower = strtolower($dest);
    foreach ($routes as $k => $info) {
        if ($k !== 'albay' && (stripos($lower, $k) !== false || stripos($lower, strtolower($info['dest_code'])) !== false || stripos($lower, strtolower($info['city'])) !== false)) {
            $matchedKey = $k;
            break;
        }
    }

    $routeInfo = $routes[$matchedKey];

    // Origin mapping
    $origins = [
        'MNL' => 'Manila (MNL T3)',
        'DGT' => 'Dumaguete (DGT)',
        'CEB' => 'Cebu (CEB)',
        'CRK' => 'Clark (CRK)',
        'KHH' => 'Kaohsiung (KHH)',
    ];

    $originName = $origins[$orig] ?? "Manila ({$orig})";

    return [
        'key'         => $matchedKey,
        'label'       => $routeInfo['label'],
        'city'        => $routeInfo['city'],
        'origin_code' => $orig,
        'origin_name' => $originName,
        'dest_code'   => $routeInfo['dest_code'],
        'dest_name'   => $routeInfo['dest_name'],
        'flight_time' => $routeInfo['flight_time'],
        'base_rate'   => $routeInfo['base_rate'],
    ];
}

/**
 * Ensures the flights table exists with proper schema and indexes.
 */
function flight_ensure_table(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS `flights` (
            `id`              INT AUTO_INCREMENT PRIMARY KEY,
            `flight_number`   VARCHAR(30)  NOT NULL,
            `airline`         VARCHAR(80)  NOT NULL,
            `airline_code`    VARCHAR(10)  NOT NULL,
            `aircraft`        VARCHAR(60)  NOT NULL,
            `origin_code`     VARCHAR(10)  NOT NULL,
            `origin_name`     VARCHAR(100) NOT NULL,
            `dest_code`       VARCHAR(10)  NOT NULL,
            `dest_name`       VARCHAR(120) NOT NULL,
            `departure_time`  TIME         NOT NULL,
            `arrival_time`    TIME         NOT NULL,
            `duration`        VARCHAR(30)  NOT NULL,
            `flight_date`     DATE         NOT NULL,
            `base_price`      DECIMAL(10,2)NOT NULL,
            `stops`           VARCHAR(30)  NOT NULL DEFAULT 'Nonstop',
            `seats_available` INT          NOT NULL DEFAULT 45,
            `cabin`           VARCHAR(40)  NOT NULL DEFAULT 'Economy',
            `status`          ENUM('available','sold_out','cancelled') NOT NULL DEFAULT 'available',
            `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_route_date` (`origin_code`, `dest_code`, `flight_date`),
            INDEX `idx_dest_date`  (`dest_code`, `flight_date`),
            INDEX `idx_flight_date`(`flight_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

/**
 * Returns baseline schedule templates for airlines on a route.
 */
function flight_get_templates(array $route): array
{
    $flightTime = $route['flight_time'] ?? '1h 20m';

    // Parse duration to calculate realistic arrival times
    preg_match('/(?:(\d+)h)?\s*(?:(\d+)m)?/', $flightTime, $m);
    $hours = isset($m[1]) && $m[1] !== '' ? (int)$m[1] : 1;
    $mins  = isset($m[2]) && $m[2] !== '' ? (int)$m[2] : 20;
    $durationSecs = ($hours * 3600) + ($mins * 60);

    $calcArrival = function(string $depTime, int $addMins = 0) use ($durationSecs) {
        $depSecs = strtotime("1970-01-01 $depTime UTC");
        $arrSecs = ($depSecs + $durationSecs + ($addMins * 60)) % 86400;
        return gmdate('H:i:s', $arrSecs);
    };

    return [
        // --- MORNING (00:00 - 11:59) ---
        [
            'airline'       => 'Philippines AirAsia',
            'airline_code'  => 'Z2',
            'flight_prefix' => 'AG-204',
            'aircraft'      => 'Airbus A320',
            'departure_time'=> '05:30:00',
            'arrival_time'  => $calcArrival('05:30:00'),
            'duration'      => $flightTime,
            'stops'         => 'Nonstop',
            'price_factor'  => 1.0, // Matches promo baseline
        ],
        [
            'airline'       => 'Cebu Pacific',
            'airline_code'  => '5J',
            'flight_prefix' => '5J-514',
            'aircraft'      => 'Airbus A321neo',
            'departure_time'=> '08:45:00',
            'arrival_time'  => $calcArrival('08:45:00'),
            'duration'      => $flightTime,
            'stops'         => 'Nonstop',
            'price_factor'  => 1.15,
        ],
        [
            'airline'       => 'Philippine Airlines',
            'airline_code'  => 'PR',
            'flight_prefix' => 'PR-1814',
            'aircraft'      => 'Airbus A321',
            'departure_time'=> '11:15:00',
            'arrival_time'  => $calcArrival('11:15:00'),
            'duration'      => $flightTime,
            'stops'         => 'Nonstop',
            'price_factor'  => 1.25,
        ],

        // --- AFTERNOON (12:00 - 17:59) ---
        [
            'airline'       => 'Cebgo',
            'airline_code'  => 'DG',
            'flight_prefix' => 'DG-6022',
            'aircraft'      => 'ATR 72-600',
            'departure_time'=> '13:45:00',
            'arrival_time'  => $calcArrival('13:45:00', 10),
            'duration'      => $flightTime,
            'stops'         => 'Nonstop',
            'price_factor'  => 1.05,
        ],
        [
            'airline'       => 'Philippines AirAsia',
            'airline_code'  => 'Z2',
            'flight_prefix' => 'AG-418',
            'aircraft'      => 'Airbus A320',
            'departure_time'=> '15:20:00',
            'arrival_time'  => $calcArrival('15:20:00', 45),
            'duration'      => '2h 15m',
            'stops'         => '1 Stop',
            'price_factor'  => 0.95,
        ],
        [
            'airline'       => 'Cebu Pacific',
            'airline_code'  => '5J',
            'flight_prefix' => '5J-562',
            'aircraft'      => 'Airbus A320',
            'departure_time'=> '17:15:00',
            'arrival_time'  => $calcArrival('17:15:00'),
            'duration'      => $flightTime,
            'stops'         => 'Nonstop',
            'price_factor'  => 1.12,
        ],

        // --- EVENING (18:00 - 23:59) ---
        [
            'airline'       => 'Philippine Airlines',
            'airline_code'  => 'PR',
            'flight_prefix' => 'PR-2814',
            'aircraft'      => 'Boeing 737',
            'departure_time'=> '18:40:00',
            'arrival_time'  => $calcArrival('18:40:00'),
            'duration'      => $flightTime,
            'stops'         => 'Nonstop',
            'price_factor'  => 1.30,
        ],
        [
            'airline'       => 'Cebu Pacific',
            'airline_code'  => '5J',
            'flight_prefix' => '5J-580',
            'aircraft'      => 'Airbus A321neo',
            'departure_time'=> '20:15:00',
            'arrival_time'  => $calcArrival('20:15:00'),
            'duration'      => $flightTime,
            'stops'         => 'Nonstop',
            'price_factor'  => 1.10,
        ],
        [
            'airline'       => 'Philippines AirAsia',
            'airline_code'  => 'Z2',
            'flight_prefix' => 'AG-822',
            'aircraft'      => 'Airbus A320',
            'departure_time'=> '21:50:00',
            'arrival_time'  => $calcArrival('21:50:00'),
            'duration'      => $flightTime,
            'stops'         => 'Nonstop',
            'price_factor'  => 1.0,
        ],
        [
            'airline'       => 'Cebgo',
            'airline_code'  => 'DG',
            'flight_prefix' => 'DG-6098',
            'aircraft'      => 'ATR 72-600',
            'departure_time'=> '22:45:00',
            'arrival_time'  => $calcArrival('22:45:00', 40),
            'duration'      => '2h 10m',
            'stops'         => '1 Stop',
            'price_factor'  => 0.90,
        ],
    ];
}

/**
 * Seeds flights into the database for a specific date range if they don't already exist
 * or refreshes if evening flights are missing.
 */
function flight_seed_dates_if_needed(PDO $db, array $route, string $startDate, string $endDate): void
{
    flight_ensure_table($db);

    $origCode = $route['origin_code'];
    $destCode = $route['dest_code'];
    $templates = flight_get_templates($route);
    $baseRate = (float)$route['base_rate'];

    $start = new DateTime($startDate);
    $end   = new DateTime($endDate);
    $end->modify('+1 day'); // inclusive

    $checkStmt = $db->prepare("
        SELECT 
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN departure_time >= '18:00:00' THEN 1 ELSE 0 END) AS evening_cnt
        FROM flights 
        WHERE origin_code = :orig AND dest_code = :dest AND flight_date = :fdate
    ");

    $deleteStmt = $db->prepare("
        DELETE FROM flights 
        WHERE origin_code = :orig AND dest_code = :dest AND flight_date = :fdate
    ");

    $insertStmt = $db->prepare("
        INSERT INTO flights (
            flight_number, airline, airline_code, aircraft, origin_code, origin_name,
            dest_code, dest_name, departure_time, arrival_time, duration,
            flight_date, base_price, stops, seats_available, cabin, status
        ) VALUES (
            :fn, :al, :alc, :ac, :oc, :oname,
            :dc, :dname, :dt, :at, :dur,
            :fdate, :bp, :stops, :seats, 'Economy', :status
        )
    ");

    $period = new DatePeriod($start, new DateInterval('P1D'), $end);
    foreach ($period as $dt) {
        $dateStr = $dt->format('Y-m-d');
        $checkStmt->execute([':orig' => $origCode, ':dest' => $destCode, ':fdate' => $dateStr]);
        $check = $checkStmt->fetch();
        $totalSeeded = (int)($check['total_cnt'] ?? 0);
        $eveningSeeded = (int)($check['evening_cnt'] ?? 0);

        // If already seeded with evening flights, no need to touch
        if ($totalSeeded > 0 && $eveningSeeded > 0) {
            continue;
        }

        // If seeded previously without evening flights, clear and re-seed with complete schedule
        if ($totalSeeded > 0 && $eveningSeeded === 0) {
            $deleteStmt->execute([':orig' => $origCode, ':dest' => $destCode, ':fdate' => $dateStr]);
        }

        // Deterministic pseudo-random variation based on date and destination
        $dayNum    = (int)$dt->format('j');
        $dayOfWeek = (int)$dt->format('N'); // 1 = Mon, 7 = Sun
        $hashVal   = abs(crc32($dateStr . '_' . $destCode));

        // 1. Off-peak Mid-week Days with No Flights Available (~7% of days, e.g. certain Wednesdays / Tuesdays)
        $isNoFlightDay = (($dayOfWeek === 3 && ($hashVal % 6 === 0)) || ($dayOfWeek === 2 && ($hashVal % 9 === 0)));
        if ($isNoFlightDay) {
            continue; // Graceful empty state day
        }

        // 2. High-demand Fully Booked / Sold Out Days (~6% of days, e.g. certain peak Saturdays / dates)
        $isAllSoldOutDay = (($dayOfWeek === 6 && ($hashVal % 7 === 1)) || ($hashVal % 31 === 2));

        // Price multiplier curve (weekends/holidays higher, mid-week promotions lower)
        $mult = 1.0;
        if ($dayOfWeek === 5 || $dayOfWeek === 6 || $dayOfWeek === 7) {
            $mult = 1.25 + ($hashVal % 25) / 100;
        } elseif ($dayOfWeek === 2 || $dayOfWeek === 3 || $dayOfWeek === 4) {
            $mult = 0.85 + ($hashVal % 15) / 100; // Promo mid-week deal
        } else {
            $mult = 0.98 + ($hashVal % 20) / 100;
        }

        // Flash promo deal days (cheapest rate)
        if ($hashVal % 11 === 0 || $dayNum === 17 || $dayNum === 25 || $dayNum === 7) {
            $mult = 0.75;
        }

        foreach ($templates as $tIdx => $t) {
            $price = round(($baseRate * $t['price_factor'] * $mult) / 100) * 100;
            if ($price < 1500) $price = 1800;

            if ($isAllSoldOutDay) {
                $seats = 0;
                $status = 'sold_out';
            } else {
                // Occasional single flight slot sold out (e.g. 1 out of 10 slots)
                $isSlotSoldOut = ($tIdx === ($hashVal % count($templates)) && ($hashVal % 3 === 0));
                // Occasional flight slot filling fast
                $isSlotFillingFast = ($tIdx === (($hashVal + 3) % count($templates)) && ($hashVal % 2 === 0));

                if ($isSlotSoldOut) {
                    $seats = 0;
                    $status = 'sold_out';
                } elseif ($isSlotFillingFast) {
                    $seats = 3 + ($hashVal % 4); // 3 to 6 seats left
                    $status = 'available';
                } else {
                    $seats = 18 + ($hashVal % 42); // 18 to 59 seats left
                    $status = 'available';
                }
            }

            $flightNo = $t['flight_prefix'];
            $insertStmt->execute([
                ':fn'    => $flightNo,
                ':al'    => $t['airline'],
                ':alc'   => $t['airline_code'],
                ':ac'    => $t['aircraft'],
                ':oc'    => $origCode,
                ':oname' => $route['origin_name'],
                ':dc'    => $destCode,
                ':dname' => $route['label'],
                ':dt'    => $t['departure_time'],
                ':at'    => $t['arrival_time'],
                ':dur'   => $t['duration'],
                ':fdate' => $dateStr,
                ':bp'    => $price,
                ':stops' => $t['stops'],
                ':seats' => $seats,
                ':status'=> $status,
            ]);
        }
    }
}

/**
 * Formats a number to Cebu Pacific K-notation (e.g. 2200 -> "2.2K", 17800 -> "17.8K", 850 -> "850").
 */
function flight_format_k(float $amount): string
{
    if ($amount >= 1000) {
        $k = $amount / 1000;
        if (floor($k) == $k) {
            return number_format($k, 0) . 'K';
        } else {
            $formatted = number_format($k, 1);
            if (str_ends_with($formatted, '.0')) {
                $formatted = substr($formatted, 0, -2);
            }
            return $formatted . 'K';
        }
    }
    return number_format($amount, 0);
}

/**
 * Fetches daily lowest flight pricing for a 2-month view or specific date range.
 */
function flight_get_daily_prices(string $destination, string $origin = 'MNL', ?string $startDate = null, ?string $endDate = null): array
{
    $route = flight_resolve_route($destination, $origin);

    $today = new DateTime('now');
    if (!$startDate) {
        $startDate = $today->format('Y-m-01');
    }
    if (!$endDate) {
        $endDt = new DateTime($startDate);
        $endDt->modify('+3 months');
        $endDate = $endDt->format('Y-m-t');
    }

    try {
        $db = ag_db();
        flight_seed_dates_if_needed($db, $route, $startDate, $endDate);

        $stmt = $db->prepare("
            SELECT 
                flight_date, 
                COUNT(*) AS total_flights,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available_count,
                MIN(CASE WHEN status = 'available' THEN base_price ELSE NULL END) AS min_price
            FROM flights
            WHERE origin_code = :orig 
              AND dest_code = :dest
              AND flight_date >= :sdate 
              AND flight_date <= :edate
            GROUP BY flight_date
            ORDER BY flight_date ASC
        ");

        $stmt->execute([
            ':orig'  => $route['origin_code'],
            ':dest'  => $route['dest_code'],
            ':sdate' => $startDate,
            ':edate' => $endDate,
        ]);

        $rows = $stmt->fetchAll();
        $daily = [];
        $overallMin = PHP_FLOAT_MAX;

        foreach ($rows as $r) {
            $fDate = $r['flight_date'];
            $total = (int)$r['total_flights'];
            $avail = (int)$r['available_count'];
            $price = $r['min_price'] !== null ? (float)$r['min_price'] : 0.0;

            if ($avail > 0 && $price > 0) {
                if ($price < $overallMin) {
                    $overallMin = $price;
                }
                $daily[$fDate] = [
                    'date'        => $fDate,
                    'price'       => $price,
                    'price_k'     => flight_format_k($price),
                    'price_raw'   => '₱' . number_format($price, 0),
                    'price_php'   => 'PHP ' . number_format($price, 2),
                    'has_flights' => true,
                    'is_sold_out' => false,
                    'available'   => $avail,
                ];
            } elseif ($total > 0 && $avail === 0) {
                $daily[$fDate] = [
                    'date'        => $fDate,
                    'price'       => null,
                    'price_k'     => '',
                    'price_raw'   => 'Sold Out',
                    'price_php'   => 'Sold Out',
                    'has_flights' => false,
                    'is_sold_out' => true,
                    'available'   => 0,
                ];
            } else {
                $daily[$fDate] = [
                    'date'        => $fDate,
                    'price'       => null,
                    'price_k'     => '',
                    'price_raw'   => 'No Flights',
                    'price_php'   => 'No Flights',
                    'has_flights' => false,
                    'is_sold_out' => false,
                    'available'   => 0,
                ];
            }
        }

        $lowestThreshold = ($overallMin < PHP_FLOAT_MAX) ? ($overallMin * 1.05) : 0;
        foreach ($daily as &$item) {
            $item['is_lowest'] = ($item['has_flights'] && $item['price'] <= $lowestThreshold);
        }
        unset($item);

        return [
            'success'       => true,
            'route'         => $route,
            'startDate'     => $startDate,
            'endDate'       => $endDate,
            'overallMin'    => ($overallMin < PHP_FLOAT_MAX) ? $overallMin : 0,
            'overallMinK'   => ($overallMin < PHP_FLOAT_MAX) ? flight_format_k($overallMin) : '',
            'daily'         => $daily,
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error'   => $e->getMessage(),
            'route'   => $route,
            'daily'   => [],
        ];
    }
}

/**
 * Fetches flight availability and 7-day carousel data for the destination results page.
 */
function flight_search_results(string $destination, string $origin = 'MNL', ?string $selectedDate = null, array $filters = []): array
{
    $route = flight_resolve_route($destination, $origin);

    if (!$selectedDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
        $selectedDate = date('Y-m-d');
    }

    $db = ag_db();
    
    // Seed 14 days around the selected date
    $selDt = new DateTime($selectedDate);
    $cStart = (clone $selDt)->modify('-7 days')->format('Y-m-d');
    $cEnd   = (clone $selDt)->modify('+7 days')->format('Y-m-d');
    flight_seed_dates_if_needed($db, $route, $cStart, $cEnd);

    // 1. Build 7-day horizontal carousel (-3 days to +3 days)
    $carousel = [];
    $lowestCarouselPrice = PHP_FLOAT_MAX;

    for ($i = -3; $i <= 3; $i++) {
        $dt = (clone $selDt)->modify("{$i} days");
        $dStr = $dt->format('Y-m-d');
        $label = $dt->format('D d M');

        $stmt = $db->prepare("
            SELECT 
                COUNT(*) AS total_cnt, 
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS avail_cnt,
                MIN(CASE WHEN status = 'available' THEN base_price ELSE NULL END) AS min_price
            FROM flights
            WHERE origin_code = :orig AND dest_code = :dest AND flight_date = :fdate
        ");
        $stmt->execute([':orig' => $route['origin_code'], ':dest' => $route['dest_code'], ':fdate' => $dStr]);
        $row = $stmt->fetch();

        $total = (int)($row['total_cnt'] ?? 0);
        $avail = (int)($row['avail_cnt'] ?? 0);
        $minPrice = $row['min_price'] !== null ? (float)$row['min_price'] : 0.0;

        $hasFlights = ($avail > 0 && $minPrice > 0);
        $isSoldOut  = ($total > 0 && $avail === 0);

        if ($hasFlights) {
            $priceLabel = 'PHP ' . number_format($minPrice, 2);
            $priceShort = '₱' . number_format($minPrice, 0);
            $statusStr  = 'available';
            if ($minPrice < $lowestCarouselPrice) {
                $lowestCarouselPrice = $minPrice;
            }
        } elseif ($isSoldOut) {
            $priceLabel = 'Sold Out';
            $priceShort = 'Sold Out';
            $statusStr  = 'sold_out';
        } else {
            $priceLabel = 'No Flights';
            $priceShort = 'No Flights';
            $statusStr  = 'no_flights';
        }

        $carousel[] = [
            'date'         => $dStr,
            'label'        => $label,
            'is_selected'  => ($i === 0),
            'has_flights'  => $hasFlights,
            'is_sold_out'  => $isSoldOut,
            'status'       => $statusStr,
            'price'        => $hasFlights ? $minPrice : null,
            'price_label'  => $priceLabel,
            'price_short'  => $priceShort,
            'avail_count'  => $avail,
            'total_count'  => $total,
        ];
    }

    // Tag lowest price card in the 7-day carousel
    foreach ($carousel as &$c) {
        $c['is_lowest'] = ($c['has_flights'] && $lowestCarouselPrice < PHP_FLOAT_MAX && $c['price'] <= ($lowestCarouselPrice + 1));
    }
    unset($c);

    // 2. Query flight cards for the selected date
    $sql = "SELECT * FROM flights WHERE origin_code = :orig AND dest_code = :dest AND flight_date = :fdate";
    $params = [
        ':orig'  => $route['origin_code'],
        ':dest'  => $route['dest_code'],
        ':fdate' => $selectedDate,
    ];

    if (!empty($filters['time'])) {
        $tFilter = strtolower(trim($filters['time']));
        if ($tFilter === 'morning') {
            $sql .= " AND departure_time >= '00:00:00' AND departure_time < '12:00:00'";
        } elseif ($tFilter === 'afternoon') {
            $sql .= " AND departure_time >= '12:00:00' AND departure_time < '18:00:00'";
        } elseif ($tFilter === 'evening') {
            $sql .= " AND departure_time >= '18:00:00' AND departure_time <= '23:59:59'";
        }
    }

    if (!empty($filters['stops']) && $filters['stops'] !== 'all') {
        $sql .= " AND stops = :stops";
        $params[':stops'] = $filters['stops'];
    }

    if (!empty($filters['price_range'])) {
        $pRange = trim($filters['price_range']);
        if ($pRange === 'under_3000') {
            $sql .= " AND base_price <= 3000";
        } elseif ($pRange === '3000_6000') {
            $sql .= " AND base_price > 3000 AND base_price <= 6000";
        } elseif ($pRange === 'above_6000') {
            $sql .= " AND base_price > 6000";
        }
    }

    $sort = $filters['sort'] ?? 'earliest';
    if ($sort === 'cheapest') {
        $sql .= " ORDER BY (status = 'available') DESC, base_price ASC, departure_time ASC";
    } elseif ($sort === 'fastest') {
        $sql .= " ORDER BY (status = 'available') DESC, duration ASC, departure_time ASC";
    } else {
        $sql .= " ORDER BY (status = 'available') DESC, departure_time ASC";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $flights = $stmt->fetchAll();

    $minDayPrice = PHP_FLOAT_MAX;
    foreach ($flights as $f) {
        if ($f['status'] === 'available' && (float)$f['base_price'] < $minDayPrice) {
            $minDayPrice = (float)$f['base_price'];
        }
    }

    $formattedFlights = [];
    foreach ($flights as $f) {
        $depTime = date('h:i A', strtotime($f['departure_time']));
        $arrTime = date('h:i A', strtotime($f['arrival_time']));
        $price = (float)$f['base_price'];
        $isAvail = ($f['status'] === 'available' && (int)$f['seats_available'] > 0);
        $isLowest = ($isAvail && $price <= $minDayPrice);

        $formattedFlights[] = [
            'id'             => $f['id'],
            'flight_number'  => $f['flight_number'],
            'airline'        => $f['airline'],
            'airline_code'   => $f['airline_code'],
            'aircraft'       => $f['aircraft'],
            'origin_code'    => $f['origin_code'],
            'origin_name'    => $f['origin_name'],
            'dest_code'      => $f['dest_code'],
            'dest_name'      => $f['dest_name'],
            'departure_time' => $depTime,
            'arrival_time'   => $arrTime,
            'duration'       => $f['duration'],
            'flight_date'    => $f['flight_date'],
            'base_price'     => $price,
            'price_formatted'=> 'PHP ' . number_format($price, 2),
            'price_short'    => '₱' . number_format($price, 0),
            'stops'          => $f['stops'],
            'seats'          => (int)$f['seats_available'],
            'status'         => $f['status'],
            'is_available'   => $isAvail,
            'is_lowest'      => $isLowest,
        ];
    }

    $availFlights = array_filter($formattedFlights, fn($f) => $f['is_available']);

    return [
        'success'               => true,
        'route'                 => $route,
        'selectedDate'          => $selectedDate,
        'selectedDateFormatted' => (new DateTime($selectedDate))->format('D d M Y'),
        'carousel'              => $carousel,
        'flights'               => $formattedFlights,
        'totalCount'            => count($formattedFlights),
        'availableCount'        => count($availFlights),
        'hasAvailable'          => count($availFlights) > 0,
        'isAllSoldOut'          => (count($formattedFlights) > 0 && count($availFlights) === 0),
    ];
}
