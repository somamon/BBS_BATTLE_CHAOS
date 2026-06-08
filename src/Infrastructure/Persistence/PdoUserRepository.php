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

    public function findByGoogleSub(string $googleSub): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE google_sub = ?');
        $stmt->execute([$googleSub]);
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
                (id, email, name, password_hash, google_sub, money, email_verified_at, is_bot, role, status, suspended_until, created_at)
             VALUES
                (:id, :email, :name, :password_hash, :google_sub, :money, :email_verified_at, :is_bot, :role, :status, :suspended_until, :created_at)'
        );
        $stmt->execute([
            ':id'                => $user->id,
            ':email'             => $user->email,
            ':name'              => $user->name,
            ':password_hash'     => $user->passwordHash(),
            ':google_sub'        => $user->googleSub(),
            ':money'             => $user->money(),
            ':email_verified_at' => $user->emailVerifiedAt()?->format('Y-m-d H:i:s'),
            ':is_bot'            => $user->isBot ? 1 : 0,
            ':role'              => $user->role(),
            ':status'            => $user->status(),
            ':suspended_until'   => $user->suspendedUntil()?->format('Y-m-d H:i:s'),
            ':created_at'        => $user->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function save(User $user): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
                SET money = :money,
                    email_verified_at = :email_verified_at,
                    password_hash = :password_hash,
                    google_sub = :google_sub,
                    role = :role,
                    status = :status,
                    suspended_until = :suspended_until
             WHERE id = :id'
        );
        $stmt->execute([
            ':money'             => $user->money(),
            ':email_verified_at' => $user->emailVerifiedAt()?->format('Y-m-d H:i:s'),
            ':password_hash'     => $user->passwordHash(),
            ':google_sub'        => $user->googleSub(),
            ':role'              => $user->role(),
            ':status'            => $user->status(),
            ':suspended_until'   => $user->suspendedUntil()?->format('Y-m-d H:i:s'),
            ':id'                => $user->id,
        ]);
    }

    public function delete(string $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);
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

    public function recentHumans(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE is_bot = 0 ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn (array $row): User => $this->hydrate($row), $stmt->fetchAll());
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
            googleSub:       $row['google_sub'] ?? null,
            role:            $row['role'] ?? 'user',
            status:          $row['status'] ?? 'active',
            suspendedUntil:  isset($row['suspended_until']) && $row['suspended_until'] !== null
                ? new DateTimeImmutable($row['suspended_until'])
                : null,
        );
    }
}
