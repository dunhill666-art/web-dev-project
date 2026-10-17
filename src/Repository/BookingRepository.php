<?php

namespace App\Repository;

use App\Entity\Booking;
use PDO;

class BookingRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function countTotalBookings(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status = :st");
        $stmt->execute([':st' => $status]);
        return (int)$stmt->fetchColumn();
    }

    public function getCountsByStatus(): array
    {
        $stmt = $this->pdo->query("
            SELECT status, COUNT(*) as cnt, COALESCE(SUM(grand_total), 0) as total_val
            FROM bookings
            GROUP BY status
        ");
        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $results[$row['status']] = [
                'count' => (int)$row['cnt'],
                'total' => (float)$row['total_val'],
            ];
        }
        return $results;
    }

    public function countBookingsThisMonth(): int
    {
        $stmt = $this->pdo->query("
            SELECT COUNT(*) FROM bookings 
            WHERE MONTH(booking_date) = MONTH(NOW()) AND YEAR(booking_date) = YEAR(NOW())
        ");
        return (int)$stmt->fetchColumn();
    }

    public function getTotalRevenue(): float
    {
        return (float)$this->pdo->query("
            SELECT COALESCE(SUM(grand_total), 0) FROM bookings WHERE status = 'confirmed'
        ")->fetchColumn();
    }

    public function getAverageBookingValue(): float
    {
        return (float)$this->pdo->query("
            SELECT COALESCE(AVG(grand_total), 0) FROM bookings WHERE status = 'confirmed'
        ")->fetchColumn();
    }

    public function getTotalDiscounts(): float
    {
        return (float)$this->pdo->query("
            SELECT COALESCE(SUM(discount_num), 0) FROM bookings
        ")->fetchColumn();
    }

    public function getTotalRefunds(): float
    {
        return (float)$this->pdo->query("
            SELECT COALESCE(SUM(refund_amount), 0) FROM bookings WHERE status = 'cancelled'
        ")->fetchColumn();
    }

    public function getMonthlyRevenueTrend(int $months = 6): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DATE_FORMAT(booking_date, '%Y-%m') AS month,
                   COUNT(*) AS booking_count,
                   SUM(CASE WHEN status='confirmed' THEN grand_total ELSE 0 END) AS confirmed_revenue,
                   SUM(grand_total) AS gross_sales,
                   SUM(discount_num) AS discounts
            FROM bookings
            WHERE booking_date >= DATE_SUB(NOW(), INTERVAL :m MONTH)
            GROUP BY month
            ORDER BY month ASC
        ");
        $stmt->bindValue(':m', $months, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getModeBreakdown(): array
    {
        $stmt = $this->pdo->query("
            SELECT mode, 
                   COUNT(*) AS cnt, 
                   COALESCE(SUM(grand_total), 0) AS rev
            FROM bookings 
            GROUP BY mode
            ORDER BY cnt DESC
        ");
        return $stmt->fetchAll();
    }

    public function getTopDestinations(int $limit = 6): array
    {
        $stmt = $this->pdo->prepare("
            SELECT city, 
                   COUNT(*) AS cnt, 
                   COALESCE(SUM(grand_total), 0) AS rev
            FROM bookings 
            WHERE status = 'confirmed'
            GROUP BY city 
            ORDER BY rev DESC 
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @return Booking[]
     */
    public function findRecentBookings(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare("
            SELECT b.*, u.username, u.email AS user_email
            FROM bookings b 
            JOIN users u ON u.id = b.user_id
            ORDER BY (b.status = 'pending_cancellation') DESC, b.booking_date DESC 
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => new Booking($row), $rows);
    }
}
