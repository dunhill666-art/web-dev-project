<?php

namespace App\Repository;

use App\Entity\Flight;
use PDO;

class FlightRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function countTotalFlights(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM flights")->fetchColumn();
    }

    public function countAvailableFlights(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM flights WHERE status = 'available'")->fetchColumn();
    }

    public function getTotalSeatsAvailable(): int
    {
        return (int)$this->pdo->query("SELECT COALESCE(SUM(seats_available), 0) FROM flights WHERE status = 'available'")->fetchColumn();
    }

    public function countUniqueRoutes(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(DISTINCT CONCAT(origin_code, '-', dest_code)) FROM flights")->fetchColumn();
    }

    public function getAirlineStats(): array
    {
        $stmt = $this->pdo->query("
            SELECT airline, 
                   airline_code,
                   COUNT(*) AS flight_count, 
                   ROUND(AVG(base_price), 2) AS avg_base_price,
                   SUM(seats_available) AS total_seats
            FROM flights 
            GROUP BY airline, airline_code
            ORDER BY flight_count DESC
        ");
        return $stmt->fetchAll();
    }

    public function getTopDestinationsByFlightFrequency(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare("
            SELECT dest_code, dest_name, COUNT(*) AS flight_count, MIN(base_price) AS min_price, AVG(base_price) AS avg_price
            FROM flights
            GROUP BY dest_code, dest_name
            ORDER BY flight_count DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @return Flight[]
     */
    public function findUpcomingFlights(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM flights 
            WHERE flight_date >= CURDATE() AND status = 'available'
            ORDER BY flight_date ASC, departure_time ASC 
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => new Flight($row), $rows);
    }
}
