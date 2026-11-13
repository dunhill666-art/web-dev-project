<?php
// ============================================================
// AeroGlide — database/catalog_data.php
// Central single source of truth for destinations, hotel stays,
// car rentals, fare tier upgrades, and server-side pricing recalculation.
// ============================================================

if (!function_exists('ag_get_catalog')) {
    function ag_get_catalog(): array
    {
        return [
            'albay' => [
                'label'   => 'Mayon Volcano, Albay',
                'asset'   => ['albay', 'mayon'],
                'blurb'   => 'Handpicked stays and rides for your trip to the world-famous perfect cone.',
                'hotels'  => [
                    ['name' => 'Seaside Garden Hotel',   'tier' => '4-star comfort', 'rating' => 4.5, 'price' => 5200],
                    ['name' => 'Harbor View Inn',        'tier' => '3-star cozy',    'rating' => 4.2, 'price' => 3400],
                    ['name' => 'Budget Traveler Lodge',  'tier' => '2-star basic',   'rating' => 3.9, 'price' => 1800],
                ],
                'cars' => [
                    ['name' => 'Economy Sedan', 'seats' => 4,  'price' => 1800],
                    ['name' => 'Midsize SUV',   'seats' => 7,  'price' => 3200],
                    ['name' => 'Tourist Van',   'seats' => 10, 'price' => 4500],
                ],
                'tiers' => [
                    'lite'   => ['name' => 'Lite Escape',     'desc' => 'Flight only',                 'upgrade' => 0],
                    'smart'  => ['name' => 'Smart Saver',     'desc' => 'Flight + checked baggage',    'upgrade' => 700],
                    'budget' => ['name' => 'Budget Explorer', 'desc' => 'Flight + baggage + meal',     'upgrade' => 1400],
                ],
            ],
            'boracay' => [
                'label'   => 'Boracay, Aklan',
                'asset'   => ['boracay'],
                'blurb'   => 'Powder-white sand and turquoise water on the Philippines\' most famous island.',
                'hotels'  => [
                    ['name' => 'Shangri-La Boracay Resort & Spa', 'tier' => '5-star beachfront', 'rating' => 4.8, 'price' => 8500],
                    ['name' => 'Discovery Shores Boracay',        'tier' => '4-star boutique',    'rating' => 4.6, 'price' => 6200],
                    ['name' => 'Henann Regency Resort & Spa',     'tier' => '3-star Station 2',   'rating' => 4.3, 'price' => 3800],
                ],
                'cars' => [
                    ['name' => 'Economy Sedan',   'seats' => 4,  'price' => 1800],
                    ['name' => 'Midsize SUV',     'seats' => 7,  'price' => 3200],
                    ['name' => 'Tourist Van',     'seats' => 10, 'price' => 4500],
                ],
                'tiers' => [
                    'lite'   => ['name' => 'Lite Escape',     'desc' => 'Flight only',                 'upgrade' => 0],
                    'smart'  => ['name' => 'Smart Saver',     'desc' => 'Flight + checked baggage',    'upgrade' => 700],
                    'budget' => ['name' => 'Budget Explorer', 'desc' => 'Flight + baggage + meal',     'upgrade' => 1400],
                ],
            ],
            'coron' => [
                'label'   => 'Coron, Palawan',
                'asset'   => ['coron'],
                'blurb'   => 'Limestone cliffs, hidden lagoons, and legendary WWII wreck diving.',
                'hotels'  => [
                    ['name' => 'Coron Westown Resort',      'tier' => '5-star waterfront', 'rating' => 4.7, 'price' => 7200],
                    ['name' => 'Sangat Island Dive Resort',  'tier' => '4-star dive lodge', 'rating' => 4.5, 'price' => 5400],
                    ['name' => 'Coron Soleil Garden Resort', 'tier' => '3-star town center','rating' => 4.2, 'price' => 3100],
                ],
                'cars' => [
                    ['name' => 'Economy Sedan',   'seats' => 4,  'price' => 1900],
                    ['name' => 'Midsize SUV',     'seats' => 7,  'price' => 3400],
                    ['name' => 'Tourist Van',     'seats' => 10, 'price' => 4700],
                ],
                'tiers' => [
                    'lite'   => ['name' => 'Lite Escape',     'desc' => 'Flight only',                 'upgrade' => 0],
                    'smart'  => ['name' => 'Smart Saver',     'desc' => 'Flight + checked baggage',    'upgrade' => 800],
                    'budget' => ['name' => 'Budget Explorer', 'desc' => 'Flight + baggage + meal',     'upgrade' => 1500],
                ],
            ],
            'siargao' => [
                'label'   => 'Siargao, Surigao del Norte',
                'asset'   => ['siargao'],
                'blurb'   => 'The surfing capital of the Philippines, with laid-back island vibes.',
                'hotels'  => [
                    ['name' => 'Nay Palad Hideaway', 'tier' => '5-star luxury villas', 'rating' => 4.9, 'price' => 15000],
                    ['name' => 'Bravo Beach Resort',  'tier' => '4-star beachfront',    'rating' => 4.6, 'price' => 6800],
                    ['name' => 'Kermit Surf Resort',  'tier' => '3-star surf lodge',    'rating' => 4.3, 'price' => 2900],
                ],
                'cars' => [
                    ['name' => 'Yamaha Fazzio Scooter', 'seats' => 2,  'price' => 600],
                    ['name' => 'Economy Sedan',         'seats' => 4,  'price' => 2000],
                    ['name' => 'Midsize SUV',           'seats' => 7,  'price' => 3600],
                ],
                'tiers' => [
                    'lite'   => ['name' => 'Lite Escape',     'desc' => 'Flight only',                 'upgrade' => 0],
                    'smart'  => ['name' => 'Smart Saver',     'desc' => 'Flight + checked baggage',    'upgrade' => 800],
                    'budget' => ['name' => 'Budget Explorer', 'desc' => 'Flight + baggage + meal',     'upgrade' => 1600],
                ],
            ],
            'bukidnon' => [
                'label'   => 'Bukidnon',
                'asset'   => ['bukidnon'],
                'blurb'   => 'Rolling hills, pine forests, and cool mountain adventure escapes.',
                'hotels'  => [
                    ['name' => 'Communal Ranch Lodge', 'tier' => '4-star mountain view', 'rating' => 4.7, 'price' => 4500],
                ],
                'cars' => [
                    ['name' => 'Midsize SUV',   'seats' => 7,  'price' => 3500],
                    ['name' => 'Tourist Van',   'seats' => 10, 'price' => 4800],
                    ['name' => 'Economy Sedan', 'seats' => 4,  'price' => 1900],
                ],
                'tiers' => [
                    'lite'   => ['name' => 'Lite Escape',     'desc' => 'Flight only',                 'upgrade' => 0],
                    'smart'  => ['name' => 'Smart Saver',     'desc' => 'Flight + checked baggage',    'upgrade' => 700],
                    'budget' => ['name' => 'Budget Explorer', 'desc' => 'Flight + baggage + meal',     'upgrade' => 1400],
                ],
            ],
            'chocolate' => [
                'label'   => 'Chocolate Hills, Bohol',
                'asset'   => ['chocolate'],
                'blurb'   => 'Iconic conical hills, tarsier sanctuaries, and pristine river cruises.',
                'hotels'  => [
                    ['name' => 'Bellevue Resort Bohol',      'tier' => '5-star beachfront', 'rating' => 4.8, 'price' => 7800],
                    ['name' => 'Loboc River Resort',         'tier' => '4-star eco lodge',   'rating' => 4.6, 'price' => 4200],
                ],
                'cars' => [
                    ['name' => 'Economy Sedan', 'seats' => 4,  'price' => 1800],
                    ['name' => 'Midsize SUV',   'seats' => 7,  'price' => 3300],
                    ['name' => 'Tourist Van',   'seats' => 10, 'price' => 4600],
                ],
                'tiers' => [
                    'lite'   => ['name' => 'Lite Escape',     'desc' => 'Flight only',                 'upgrade' => 0],
                    'smart'  => ['name' => 'Smart Saver',     'desc' => 'Flight + checked baggage',    'upgrade' => 700],
                    'budget' => ['name' => 'Budget Explorer', 'desc' => 'Flight + baggage + meal',     'upgrade' => 1400],
                ],
            ],
            'malapascua' => [
                'label'   => 'Malapascua Island, Cebu',
                'asset'   => ['malapascua'],
                'blurb'   => 'World-famous thresher shark diving and serene white sand beaches.',
                'hotels'  => [
                    ['name' => 'Ocean Vida Beach & Dive Resort', 'tier' => '4-star beachfront', 'rating' => 4.7, 'price' => 4900],
                    ['name' => 'Tepanee Beach Resort',           'tier' => '4-star cliffside',  'rating' => 4.5, 'price' => 4200],
                    ['name' => 'Exotic Island Dive Resort',      'tier' => '3-star dive lodge', 'rating' => 4.3, 'price' => 2800],
                ],
                'cars' => [
                    ['name' => 'Island Motorbike Rental', 'seats' => 2,  'price' => 500],
                    ['name' => 'Outrigger Boat Transfer', 'seats' => 6,  'price' => 1500],
                    ['name' => 'Economy Sedan',           'seats' => 4,  'price' => 1800],
                ],
                'tiers' => [
                    'lite'   => ['name' => 'Lite Escape',     'desc' => 'Flight only',                 'upgrade' => 0],
                    'smart'  => ['name' => 'Smart Saver',     'desc' => 'Flight + checked baggage',    'upgrade' => 700],
                    'budget' => ['name' => 'Budget Explorer', 'desc' => 'Flight + baggage + meal',     'upgrade' => 1400],
                ],
            ],
            'taal' => [
                'label'   => 'Taal Volcano, Batangas',
                'asset'   => ['taal'],
                'blurb'   => 'Stunning volcanic crater lake views and relaxing cool highland breezes.',
                'hotels'  => [
                    ['name' => 'Taalisay Ridge Resort',   'tier' => '4-star volcano view', 'rating' => 4.6, 'price' => 5400],
                    ['name' => 'Lakeview Haven Hotel',    'tier' => '3-star lakeside',     'rating' => 4.3, 'price' => 3100],
                    ['name' => 'Batangas Travelers Inn', 'tier' => '2-star budget',       'rating' => 3.9, 'price' => 1600],
                ],
                'cars' => [
                    ['name' => 'Economy Sedan', 'seats' => 4,  'price' => 1700],
                    ['name' => 'Midsize SUV',   'seats' => 7,  'price' => 3100],
                    ['name' => 'Tourist Van',   'seats' => 10, 'price' => 4400],
                ],
                'tiers' => [
                    'lite'   => ['name' => 'Lite Escape',     'desc' => 'Flight only',                 'upgrade' => 0],
                    'smart'  => ['name' => 'Smart Saver',     'desc' => 'Flight + checked baggage',    'upgrade' => 700],
                    'budget' => ['name' => 'Budget Explorer', 'desc' => 'Flight + baggage + meal',     'upgrade' => 1400],
                ],
            ],
            'default' => [
                'label'   => null,
                'asset'   => ['albay', 'coron'],
                'blurb'   => 'Handpicked stays and rides for your trip.',
                'hotels'  => [
                    ['name' => 'Seaside Garden Hotel',   'tier' => '4-star comfort', 'rating' => 4.5, 'price' => 5200],
                    ['name' => 'Harbor View Inn',        'tier' => '3-star cozy',    'rating' => 4.2, 'price' => 3400],
                    ['name' => 'Budget Traveler Lodge',  'tier' => '2-star basic',   'rating' => 3.9, 'price' => 1800],
                ],
                'cars' => [
                    ['name' => 'Economy Sedan', 'seats' => 4,  'price' => 1800],
                    ['name' => 'Midsize SUV',   'seats' => 7,  'price' => 3200],
                    ['name' => 'Tourist Van',   'seats' => 10, 'price' => 4500],
                ],
                'tiers' => [
                    'lite'   => ['name' => 'Lite Escape',     'desc' => 'Flight only',                 'upgrade' => 0],
                    'smart'  => ['name' => 'Smart Saver',     'desc' => 'Flight + checked baggage',    'upgrade' => 700],
                    'budget' => ['name' => 'Budget Explorer', 'desc' => 'Flight + baggage + meal',     'upgrade' => 1400],
                ],
            ],
        ];
    }
}

if (!function_exists('ag_resolve_catalog_city')) {
    function ag_resolve_catalog_city(string $city): array
    {
        $catalog = ag_get_catalog();
        $cityKey = 'default';
        $lower   = strtolower(trim($city));

        foreach (array_keys($catalog) as $key) {
            if ($key !== 'default' && stripos($lower, $key) !== false) {
                $cityKey = $key;
                break;
            }
        }

        $res = $catalog[$cityKey];
        if (empty($res['label'])) {
            $res['label'] = $city !== '' ? $city : 'Mayon Volcano, Albay';
        }
        $res['key'] = $cityKey;
        return $res;
    }
}

if (!function_exists('ag_resolve_tier')) {
    function ag_resolve_tier(string $fareInput, string $cityKey): array
    {
        $catalog = ag_get_catalog();
        $dest = $catalog[$cityKey] ?? $catalog['default'];
        $tiers = $dest['tiers'];

        $clean = strtolower(trim($fareInput));
        if (str_contains($clean, 'budget') || $clean === 'budget') {
            return array_merge(['key' => 'budget'], $tiers['budget']);
        }
        if (str_contains($clean, 'lite') || $clean === 'lite') {
            return array_merge(['key' => 'lite'], $tiers['lite']);
        }
        // Default to Smart Saver
        return array_merge(['key' => 'smart'], $tiers['smart']);
    }
}

if (!function_exists('ag_cabin_multiplier')) {
    function ag_cabin_multiplier(string $cabin): float
    {
        if (stripos($cabin, 'Premium') !== false) return 1.3;
        if (stripos($cabin, 'Business') !== false) return 1.8;
        if (stripos($cabin, 'First') !== false) return 2.5;
        return 1.0;
    }
}

