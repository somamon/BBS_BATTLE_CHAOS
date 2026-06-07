<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\PasswordReset;
use App\Domain\Repository\PasswordResetRepository;
use DateTimeImmutable;
use PDO;

final class PdoPasswordResetRepository implements PasswordResetRepository
{
    public function __construct(private PDO $pdo) {}

    public function insert(PasswordReset $reset): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO password_resets (token_hash, user_id, expires_at, created_at)
             VALUES (:token_hash, :user_id, :expires_at, :created_at)'
        );
        $stmt->execute([
            ':token_hash' => $reset->tokenHash,
            ':user_id'    => $reset->userId,
            ':expires_at' => $reset->expiresAt->format('Y-m-d H:i:s'),
            ':created_at' => $reset->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findByTokenHash(string $tokenHash): ?PasswordReset
    {
        $stmt = $this->pdo->prepare('SELECT * FROM password_resets WHERE token_hash = ?');
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function deleteForUser(string $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
        $stmt->execute([$userId]);
    }

    public function purgeExpired(\DateTimeImmutable $now): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM password_resets WHERE expires_at < ?');
        $stmt->execute([$now->format('Y-m-d H:i:s')]);
    }

    private function hydrate(array $row): PasswordReset
    {
        return new PasswordReset(
            tokenHash: $row['token_hash'],
            userId:    $row['user_id'],
            expiresAt: new DateTimeImmutable($row['expires_at']),
            createdAt: new DateTimeImmutable($row['created_at']),
        );
    }
}
