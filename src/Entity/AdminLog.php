<?php

namespace App\Entity;

class AdminLog
{
    private ?int $id = null;
    private int $adminId = 0;
    private string $action = '';
    private ?string $target = null;
    private string $createdAt = '';
    private ?string $adminName = null;
    private ?string $adminUsername = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = isset($data['id']) ? (int)$data['id'] : null;
            $this->adminId = (int)($data['admin_id'] ?? 0);
            $this->action = $data['action'] ?? '';
            $this->target = $data['target'] ?? null;
            $this->createdAt = $data['created_at'] ?? '';
            $this->adminName = $data['admin_name'] ?? null;
            $this->adminUsername = $data['admin_username'] ?? null;
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getAdminId(): int { return $this->adminId; }
    public function getAction(): string { return $this->action; }
    public function getTarget(): ?string { return $this->target; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getAdminName(): ?string { return $this->adminName; }
    public function getAdminUsername(): ?string { return $this->adminUsername; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'admin_id' => $this->adminId,
            'action' => $this->action,
            'target' => $this->target,
            'created_at' => $this->createdAt,
            'admin_name' => $this->adminName,
            'admin_username' => $this->adminUsername,
        ];
    }
}
