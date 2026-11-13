<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 60, unique: true)]
    private string $username = '';

    #[ORM\Column(type: 'string', length: 120)]
    private string $name = '';

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $password = '';

    #[ORM\Column(type: 'string', length: 20)]
    private string $role = 'user';

    #[ORM\Column(name: 'used_coupon', type: 'string', length: 30, nullable: true)]
    private ?string $usedCoupon = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'last_login', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = isset($data['id']) ? (int)$data['id'] : null;
            $this->username = $data['username'] ?? '';
            $this->name = $data['name'] ?? '';
            $this->email = $data['email'] ?? '';
            $this->password = $data['password'] ?? '';
            $this->role = $data['role'] ?? 'user';
            $this->usedCoupon = $data['used_coupon'] ?? null;
            
            if (!empty($data['created_at'])) {
                $this->createdAt = is_string($data['created_at']) 
                    ? new \DateTime($data['created_at']) 
                    : $data['created_at'];
            }
            if (!empty($data['last_login'])) {
                $this->lastLogin = is_string($data['last_login']) 
                    ? new \DateTime($data['last_login']) 
                    : $data['last_login'];
            }
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }

    public function getUsedCoupon(): ?string { return $this->usedCoupon; }
    public function setUsedCoupon(?string $usedCoupon): self { $this->usedCoupon = $usedCoupon; return $this; }

    public function getCreatedAt(): ?string 
    { 
        return $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null; 
    }
    public function setCreatedAt(?\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getLastLogin(): ?string 
    { 
        return $this->lastLogin ? $this->lastLogin->format('Y-m-d H:i:s') : null; 
    }
    public function setLastLogin(?\DateTimeInterface $lastLogin): self { $this->lastLogin = $lastLogin; return $this; }

    public function isAdmin(): bool { return $this->role === 'admin'; }

    /**
     * Symfony Security UserInterface methods
     */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->role === 'admin') {
            $roles[] = 'ROLE_ADMIN';
        }
        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'used_coupon' => $this->usedCoupon,
            'created_at' => $this->getCreatedAt(),
            'last_login' => $this->getLastLogin(),
        ];
    }
}
