<?php

namespace App\Repository;

use App\Entity\AdminLog;
use PDO;

class AdminLogRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function countTotalLogs(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM admin_logs")->fetchColumn();
    }

    /**
     * @return AdminLog[]
     */
    public function findRecentLogs(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare("
            SELECT l.*, u.name AS admin_name, u.username AS admin_username
            FROM admin_logs l
            LEFT JOIN users u ON u.id = l.admin_id
            ORDER BY l.created_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => new AdminLog($row), $rows);
    }
}
