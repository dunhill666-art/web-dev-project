<?php

namespace App\Entity;

class User
{
    private ?int $id = null;
    private string $username = '';
    private string $name = '';
    private string $email = '';
    private string $role = 'user';
    private ?string $usedCoupon = null;
    private ?string $createdAt = null;
    private ?string $lastLogin = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = isset($data['id']) ? (int)$data['id'] : null;
            $this->username = $data['username'] ?? '';
            $this->name = $data['name'] ?? '';
            $this->email = $data['email'] ?? '';
            $this->role = $data['role'] ?? 'user';
            $this->usedCoupon = $data['used_coupon'] ?? null;
            $this->createdAt = $data['created_at'] ?? null;
            $this->lastLogin = $data['last_login'] ?? null;
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getRole(): string { return $this->role; }
    public function getUsedCoupon(): ?string { return $this->usedCoupon; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getLastLogin(): ?string { return $this->lastLogin; }

    public function isAdmin(): bool { return $this->role === 'admin'; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'used_coupon' => $this->usedCoupon,
            'created_at' => $this->createdAt,
            'last_login' => $this->lastLogin,
        ];
    }
}
