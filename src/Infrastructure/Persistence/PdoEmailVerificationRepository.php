<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\EmailVerification;
use App\Domain\Repository\EmailVerificationRepository;
use DateTimeImmutable;
use PDO;

final class PdoEmailVerificationRepository implements EmailVerificationRepository
{
    public function __construct(private PDO $pdo) {}

    public function insert(EmailVerification $verification): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO email_verifications (token_hash, user_id, expires_at, created_at)
             VALUES (:token_hash, :user_id, :expires_at, :created_at)'
        );
        $stmt->execute([
            ':token_hash' => $verification->tokenHash,
            ':user_id'    => $verification->userId,
            ':expires_at' => $verification->expiresAt->format('Y-m-d H:i:s'),
            ':created_at' => $verification->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findByTokenHash(string $tokenHash): ?EmailVerification
    {
        $stmt = $this->pdo->prepare('SELECT * FROM email_verifications WHERE token_hash = ?');
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function deleteForUser(string $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?');
        $stmt->execute([$userId]);
    }

    private function hydrate(array $row): EmailVerification
    {
        return new EmailVerification(
            tokenHash: $row['token_hash'],
            userId:    $row['user_id'],
            expiresAt: new DateTimeImmutable($row['expires_at']),
            createdAt: new DateTimeImmutable($row['created_at']),
        );
    }
}
