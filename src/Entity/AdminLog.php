<?php

namespace App\Entity;

use App\Repository\AdminLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminLogRepository::class)]
#[ORM\Table(name: 'admin_logs')]
class AdminLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'admin_id', type: 'integer')]
    private int $adminId = 0;

    #[ORM\Column(type: 'string', length: 200)]
    private string $action = '';

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $target = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    private ?string $adminName = null;
    private ?string $adminUsername = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = isset($data['id']) ? (int)$data['id'] : null;
            $this->adminId = (int)($data['admin_id'] ?? 0);
            $this->action = $data['action'] ?? '';
            $this->target = $data['target'] ?? null;
            if (!empty($data['created_at'])) {
                $this->createdAt = is_string($data['created_at']) ? new \DateTime($data['created_at']) : $data['created_at'];
            }
            $this->adminName = $data['admin_name'] ?? null;
            $this->adminUsername = $data['admin_username'] ?? null;
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getAdminId(): int { return $this->adminId; }
    public function getAction(): string { return $this->action; }
    public function getTarget(): ?string { return $this->target; }
    public function getCreatedAt(): ?string { return $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null; }
    public function getAdminName(): ?string { return $this->adminName; }
    public function getAdminUsername(): ?string { return $this->adminUsername; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'admin_id' => $this->adminId,
            'action' => $this->action,
            'target' => $this->target,
            'created_at' => $this->getCreatedAt(),
            'admin_name' => $this->adminName,
            'admin_username' => $this->adminUsername,
        ];
    }
}
