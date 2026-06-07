<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;
use PDO;

final class PdoUserRepository implements UserRepository
{
    public function __construct(private PDO $pdo) {}

    public function findById(string $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function existsByEmail(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([$email]);

        return (bool) $stmt->fetchColumn();
    }

    public function insert(User $user): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users
                (id, email, name, password_hash, money, email_verified_at, is_bot, created_at)
             VALUES
                (:id, :email, :name, :password_hash, :money, :email_verified_at, :is_bot, :created_at)'
        );
        $stmt->execute([
            ':id'                => $user->id,
            ':email'             => $user->email,
            ':name'              => $user->name,
            ':password_hash'     => $user->passwordHash,
            ':money'             => $user->money(),
            ':email_verified_at' => $user->emailVerifiedAt()?->format('Y-m-d H:i:s'),
            ':is_bot'            => $user->isBot ? 1 : 0,
            ':created_at'        => $user->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function save(User $user): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET money = :money, email_verified_at = :email_verified_at WHERE id = :id'
        );
        $stmt->execute([
            ':money'             => $user->money(),
            ':email_verified_at' => $user->emailVerifiedAt()?->format('Y-m-d H:i:s'),
            ':id'                => $user->id,
        ]);
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM users');

        return array_map(
            fn (array $row): User => $this->hydrate($row),
            $stmt->fetchAll()
        );
    }

    public function countHumans(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users WHERE is_bot = 0')->fetchColumn();
    }

    public function bots(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM users WHERE is_bot = 1');

        return array_map(
            fn (array $row): User => $this->hydrate($row),
            $stmt->fetchAll()
        );
    }

    private function hydrate(array $row): User
    {
        return new User(
            id:              $row['id'],
            email:           $row['email'],
            name:            $row['name'],
            passwordHash:    $row['password_hash'],
            money:           (int) $row['money'],
            createdAt:       new DateTimeImmutable($row['created_at']),
            emailVerifiedAt: $row['email_verified_at'] !== null
                ? new DateTimeImmutable($row['email_verified_at'])
                : null,
            isBot:           (bool) $row['is_bot'],
        );
    }
}
