<?php

/*
|--------------------------------------------------------------------------
| AEROGLIDE DATABASE SETUP
|--------------------------------------------------------------------------
| This file creates the AeroGlide database and all required tables.
|
| Run it through:
| http://localhost/website/database.php
|
| MySQL:
| Host: localhost
| Username: root
| Password: (blank)
|--------------------------------------------------------------------------
*/


// ============================================================
// DATABASE CONNECTION
// ============================================================

$host = "localhost";
$username = "root";
$password = "";

try {

    // Connect to MySQL
    $pdo = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "Connected to MySQL successfully.<br>";


    // ========================================================
    // CREATE DATABASE
    // ========================================================

    $pdo->exec("
        CREATE DATABASE IF NOT EXISTS aeroglide
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci
    ");

    echo "Database 'aeroglide' created successfully.<br>";


    // Select database
    $pdo->exec("USE aeroglide");


    // ========================================================
    // USERS TABLE
    // ========================================================

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (

            id INT AUTO_INCREMENT PRIMARY KEY,

            username VARCHAR(50) NOT NULL UNIQUE,

            email VARCHAR(100) NOT NULL UNIQUE,

            password VARCHAR(255) NOT NULL,

            first_name VARCHAR(50),

            last_name VARCHAR(50),

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP

        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    echo "Users table created.<br>";


    // ========================================================
    // FLIGHTS TABLE
    // ========================================================

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS flights (

            id INT AUTO_INCREMENT PRIMARY KEY,

            flight_number VARCHAR(20) NOT NULL,

            airline VARCHAR(100) NOT NULL,

            departure_airport VARCHAR(10) NOT NULL,

            arrival_airport VARCHAR(10) NOT NULL,

            departure_time DATETIME NOT NULL,

            arrival_time DATETIME NOT NULL,

            price DECIMAL(10,2) NOT NULL,

            available_seats INT NOT NULL DEFAULT 0,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP

        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    echo "Flights table created.<br>";


    // ========================================================
    // HOTELS TABLE
    // ========================================================

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hotels (

            id INT AUTO_INCREMENT PRIMARY KEY,

            name VARCHAR(200) NOT NULL,

            address VARCHAR(255),

            city VARCHAR(100),

            country VARCHAR(100),

            stars INT DEFAULT 0,

            price_per_night DECIMAL(10,2) NOT NULL,

            available_rooms INT NOT NULL DEFAULT 0,

            description TEXT,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP

        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    echo "Hotels table created.<br>";


    // ========================================================
    // CARS TABLE
    // ========================================================

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cars (

            id INT AUTO_INCREMENT PRIMARY KEY,

            make VARCHAR(50) NOT NULL,

            model VARCHAR(50) NOT NULL,

            year INT NOT NULL,

            category VARCHAR(50) NOT NULL,

            price_per_day DECIMAL(10,2) NOT NULL,

            available_cars INT NOT NULL DEFAULT 0,

            transmission VARCHAR(20) DEFAULT 'automatic',

            fuel_type VARCHAR(20),

            ac BOOLEAN DEFAULT TRUE,

            description TEXT,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP

        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    echo "Cars table created.<br>";


    // ========================================================
    // DESTINATIONS TABLE
    // ========================================================

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS destinations (

            id INT AUTO_INCREMENT PRIMARY KEY,

            city VARCHAR(100) NOT NULL,

            country VARCHAR(100) NOT NULL,

            image_url VARCHAR(500),

            price_from DECIMAL(10,2) NOT NULL,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP

        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    echo "Destinations table created.<br>";


    // ========================================================
    // DEALS TABLE
    // ========================================================

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS deals (

            id INT AUTO_INCREMENT PRIMARY KEY,

            route VARCHAR(255) NOT NULL,

            image_url VARCHAR(500),

            meta_info VARCHAR(255),

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP

        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    echo "Deals table created.<br>";


    // ========================================================
    // FARE OPTIONS TABLE
    // ========================================================

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fare_options (

            id INT AUTO_INCREMENT PRIMARY KEY,

            deal_id INT NOT NULL,

            fare_name VARCHAR(50) NOT NULL,

            price DECIMAL(10,2) NOT NULL,

            fare_note VARCHAR(255),

            is_selected BOOLEAN DEFAULT FALSE,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            CONSTRAINT fk_fare_deal

                FOREIGN KEY (deal_id)

                REFERENCES deals(id)

                ON DELETE CASCADE

                ON UPDATE CASCADE

        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    echo "Fare options table created.<br>";


    // ========================================================
    // SAMPLE USERS
    // ========================================================

    // Password for both accounts = "password"
    $hashedPassword = password_hash("password", PASSWORD_DEFAULT);


    $stmt = $pdo->prepare("
        INSERT IGNORE INTO users
        (
            username,
            email,
            password,
            first_name,
            last_name
        )
        VALUES (?, ?, ?, ?, ?)
    ");


    $stmt->execute([
        "admin",
        "admin@aeroglide.com",
        $hashedPassword,
        "Admin",
        "User"
    ]);


    $stmt->execute([
        "john_doe",
        "john@example.com",
        $hashedPassword,
        "John",
        "Doe"
    ]);


    echo "Sample users inserted.<br>";


    // ========================================================
    // SAMPLE FLIGHTS
    // ========================================================

    $flightCount = $pdo->query("
        SELECT COUNT(*) FROM flights
    ")->fetchColumn();


    if ($flightCount == 0) {

        $stmt = $pdo->prepare("
            INSERT INTO flights
            (
                flight_number,
                airline,
                departure_airport,
                arrival_airport,
                departure_time,
                arrival_time,
                price,
                available_seats
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");


        $flights = [

            [
                "AG101",
                "AeroGlide",
                "MNL",
                "CEB",
                "2026-09-15 08:00:00",
                "2026-09-15 09:30:00",
                2499.00,
                150
            ],

            [
                "AG102",
                "AeroGlide",
                "CEB",
                "MNL",
                "2026-09-15 11:00:00",
                "2026-09-15 12:30:00",
                2599.00,
                145
            ],

            [
                "AG201",
                "AeroGlide",
                "MNL",
                "DVO",
                "2026-09-16 07:30:00",
                "2026-09-16 09:30:00",
                3199.00,
                120
            ],

            [
                "AG202",
                "AeroGlide",
                "DVO",
                "MNL",
                "2026-09-16 14:00:00",
                "2026-09-16 16:00:00",
                3299.00,
                115
            ],

            [
                "AG301",
                "AeroGlide",
                "MNL",
                "PPS",
                "2026-09-17 09:00:00",
                "2026-09-17 10:30:00",
                2899.00,
                100
            ],

            [
                "AG401",
                "AeroGlide",
                "MNL",
                "HKG",
                "2026-09-18 06:30:00",
                "2026-09-18 08:45:00",
                5899.00,
                130
            ],

            [
                "AG501",
                "AeroGlide",
                "MNL",
                "NRT",
                "2026-09-19 05:00:00",
                "2026-09-19 10:30:00",
                10999.00,
                180
            ],

            [
                "AG601",
                "AeroGlide",
                "MNL",
                "SIN",
                "2026-09-20 12:00:00",
                "2026-09-20 15:45:00",
                7499.00,
                160
            ]

        ];


        foreach ($flights as $flight) {
            $stmt->execute($flight);
        }

        echo "Sample flights inserted.<br>";

    } else {

        echo "Flights already contain data. Skipping sample flights.<br>";

    }


    // ========================================================
    // SAMPLE HOTELS
    // ========================================================

    $hotelCount = $pdo->query("
        SELECT COUNT(*) FROM hotels
    ")->fetchColumn();


    if ($hotelCount == 0) {

        $stmt = $pdo->prepare("
            INSERT INTO hotels
            (
                name,
                address,
                city,
                country,
                stars,
                price_per_night,
                available_rooms,
                description
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");


        $hotels = [

            [
                "Manila Grand Hotel",
                "Roxas Boulevard",
                "Manila",
                "Philippines",
                5,
                5500.00,
                35,
                "Luxury hotel near Manila Bay."
            ],

            [
                "Cebu Seaside Resort",
                "Mactan Island",
                "Cebu",
                "Philippines",
                4,
                4200.00,
                40,
                "Beachside resort near Mactan Cebu International Airport."
            ],

            [
                "Davao Central Hotel",
                "J.P. Laurel Avenue",
                "Davao",
                "Philippines",
                4,
                3500.00,
                30,
                "Comfortable hotel located near central Davao."
            ],

            [
                "Palawan Paradise Resort",
                "Puerto Princesa",
                "Palawan",
                "Philippines",
                5,
                6200.00,
                25,
                "Island-inspired resort ideal for vacations."
            ],

            [
                "Tokyo Grand Hotel",
                "Shinjuku",
                "Tokyo",
                "Japan",
                5,
                12500.00,
                20,
                "Modern luxury accommodation in Tokyo."
            ],

            [
                "Singapore City Suites",
                "Marina Bay",
                "Singapore",
                "Singapore",
                5,
                13500.00,
                18,
                "Premium hotel overlooking Marina Bay."
            ]

        ];


        foreach ($hotels as $hotel) {
            $stmt->execute($hotel);
        }

        echo "Sample hotels inserted.<br>";

    } else {

        echo "Hotels already contain data. Skipping sample hotels.<br>";

    }


    // ========================================================
    // SAMPLE CARS
    // ========================================================

    $carCount = $pdo->query("
        SELECT COUNT(*) FROM cars
    ")->fetchColumn();


    if ($carCount == 0) {

        $stmt = $pdo->prepare("
            INSERT INTO cars
            (
                make,
                model,
                year,
                category,
                price_per_day,
                available_cars,
                transmission,
                fuel_type,
                ac,
                description
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");


        $cars = [

            [
                "Toyota",
                "Vios",
                2025,
                "Sedan",
                1800.00,
                20,
                "Automatic",
                "Gasoline",
                true,
                "Affordable and fuel-efficient sedan."
            ],

            [
                "Toyota",
                "Fortuner",
                2025,
                "SUV",
                3500.00,
                10,
                "Automatic",
                "Diesel",
                true,
                "Large SUV suitable for family trips."
            ],

            [
                "Honda",
                "City",
                2025,
                "Sedan",
                2000.00,
                15,
                "Automatic",
                "Gasoline",
                true,
                "Comfortable compact sedan."
            ],

            [
                "Honda",
                "CR-V",
                2025,
                "SUV",
                3200.00,
                12,
                "Automatic",
                "Gasoline",
                true,
                "Spacious SUV for long-distance travel."
            ],

            [
                "Mitsubishi",
                "Montero Sport",
                2025,
                "SUV",
                3600.00,
                8,
                "Automatic",
                "Diesel",
                true,
                "Premium SUV with spacious seating."
            ],

            [
                "Toyota",
                "Hiace",
                2025,
                "Van",
                4500.00,
                6,
                "Automatic",
                "Diesel",
                true,
                "Passenger van suitable for large groups."
            ]

        ];


        foreach ($cars as $car) {
            $stmt->execute($car);
        }

        echo "Sample cars inserted.<br>";

    } else {

        echo "Cars already contain data. Skipping sample cars.<br>";

    }


    // ========================================================
    // SAMPLE DESTINATIONS
    // ========================================================

    $destinationCount = $pdo->query("
        SELECT COUNT(*) FROM destinations
    ")->fetchColumn();


    if ($destinationCount == 0) {

        $stmt = $pdo->prepare("
            INSERT INTO destinations
            (
                city,
                country,
                image_url,
                price_from
            )
            VALUES (?, ?, ?, ?)
        ");


        $destinations = [

            [
                "Rome",
                "Italy",
                "asset/rome.jpg",
                29999.00
            ],

            [
                "Tokyo",
                "Japan",
                "asset/tokyo.jpg",
                12999.00
            ],

            [
                "Santorini",
                "Greece",
                "asset/santorini.jpg",
                34999.00
            ],

            [
                "Reykjavik",
                "Iceland",
                "asset/reykjavik.jpg",
                39999.00
            ],

            [
                "Marrakesh",
                "Morocco",
                "asset/marrakesh.jpg",
                32999.00
            ]

        ];


        foreach ($destinations as $destination) {
            $stmt->execute($destination);
        }

        echo "Sample destinations inserted.<br>";

    } else {

        echo "Destinations already contain data. Skipping sample destinations.<br>";

    }


    // ========================================================
    // SAMPLE DEALS
    // ========================================================

    $dealCount = $pdo->query("
        SELECT COUNT(*) FROM deals
    ")->fetchColumn();


    if ($dealCount == 0) {

        $stmt = $pdo->prepare("
            INSERT INTO deals
            (
                route,
                image_url,
                meta_info
            )
            VALUES (?, ?, ?)
        ");


        $deals = [

            [
                "New York → Barcelona",
                "asset/barcelona.jpg",
                "Round trip · Economy"
            ],

            [
                "London → Tokyo",
                "asset/tokyo.jpg",
                "Round trip · Economy"
            ],

            [
                "San Francisco → Lisbon",
                "asset/lisbon.jpg",
                "Round trip · Economy"
            ]

        ];


        foreach ($deals as $deal) {
            $stmt->execute($deal);
        }

        echo "Sample deals inserted.<br>";

    } else {

        echo "Deals already contain data. Skipping sample deals.<br>";

    }


    // ========================================================
    // SAMPLE FARE OPTIONS
    // ========================================================

    $fareCount = $pdo->query("
        SELECT COUNT(*) FROM fare_options
    ")->fetchColumn();


    if ($fareCount == 0) {

        $stmt = $pdo->prepare("
            INSERT INTO fare_options
            (
                deal_id,
                fare_name,
                price,
                fare_note,
                is_selected
            )
            VALUES (?, ?, ?, ?, ?)
        ");


        $fares = [

            // Deal 1
            [
                1,
                "Basic",
                318.00,
                "Carry-on only · no changes",
                true
            ],

            [
                1,
                "Main",
                405.00,
                "Seat + meal · one checked bag",
                false
            ],

            [
                1,
                "Flex",
                589.00,
                "Free changes · refundable",
                false
            ],


            // Deal 2
            [
                2,
                "Basic",
                520.00,
                "Carry-on only · no changes",
                true
            ],

            [
                2,
                "Main",
                675.00,
                "Seat + meal · one checked bag",
                false
            ],

            [
                2,
                "Flex",
                850.00,
                "Free changes · refundable",
                false
            ],


            // Deal 3
            [
                3,
                "Basic",
                395.00,
                "Carry-on only · no changes",
                true
            ],

            [
                3,
                "Main",
                490.00,
                "Seat + meal · one checked bag",
                false
            ],

            [
                3,
                "Flex",
                650.00,
                "Free changes · refundable",
                false
            ]

        ];


        foreach ($fares as $fare) {
            $stmt->execute($fare);
        }

        echo "Sample fare options inserted.<br>";

    } else {

        echo "Fare options already contain data. Skipping sample fares.<br>";

    }


    // ========================================================
    // FINISHED
    // ========================================================

    echo "<br>";
    echo "<strong>AeroGlide database setup completed successfully!</strong><br>";
    echo "<br>";
    echo "Database: aeroglide<br>";
    echo "Tables: users, flights, hotels, cars, destinations, deals, fare_options<br>";
    echo "<br>";
    echo "Test login accounts:<br>";
    echo "Username: admin | Password: password<br>";
    echo "Username: john_doe | Password: password<br>";


} catch (PDOException $e) {

    echo "<strong>Database setup failed.</strong><br>";
    echo "Error: " . $e->getMessage();

}

?>