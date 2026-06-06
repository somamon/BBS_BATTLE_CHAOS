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
                (id, email, name, password_hash, money, created_at)
             VALUES
                (:id, :email, :name, :password_hash, :money, :created_at)'
        );
        $stmt->execute([
            ':id'            => $user->id,
            ':email'         => $user->email,
            ':name'          => $user->name,
            ':password_hash' => $user->passwordHash,
            ':money'         => $user->money(),
            ':created_at'    => $user->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function save(User $user): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET money = :money WHERE id = :id');
        $stmt->execute([
            ':money' => $user->money(),
            ':id'    => $user->id,
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

    private function hydrate(array $row): User
    {
        return new User(
            id:           $row['id'],
            email:        $row['email'],
            name:         $row['name'],
            passwordHash: $row['password_hash'],
            money:        (int) $row['money'],
            createdAt:    new DateTimeImmutable($row['created_at']),
        );
    }
}
