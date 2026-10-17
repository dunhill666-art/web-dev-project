<?php

namespace App\Repository;

use App\Entity\User;
use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function countTotalUsers(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE role='user'");
        return (int)$stmt->fetchColumn();
    }

    public function countNewUsersThisMonth(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE role='user' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())");
        return (int)$stmt->fetchColumn();
    }

    /**
     * @return User[]
     */
    public function findRecentUsers(int $limit = 6): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => new User($row), $rows);
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? new User($row) : null;
    }

    public function getUserGrowthStats(): array
    {
        $stmt = $this->pdo->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS count
            FROM users
            WHERE role='user' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY month
            ORDER BY month ASC
        ");
        return $stmt->fetchAll();
    }
}
