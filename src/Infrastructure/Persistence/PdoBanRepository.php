<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Ban;
use App\Domain\Repository\BanRepository;
use DateTimeImmutable;
use PDO;

final class PdoBanRepository implements BanRepository
{
    public function __construct(private PDO $pdo) {}

    public function isBanned(string $kind, string $value, ?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable();
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM bans WHERE kind = ? AND value = ? AND (expires_at IS NULL OR expires_at > ?) LIMIT 1'
        );
        $stmt->execute([$kind, $value, $now->format('Y-m-d H:i:s')]);
        return (bool) $stmt->fetchColumn();
    }

    public function insert(Ban $ban): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO bans (kind, value, reason, created_by, expires_at, created_at)
             VALUES (:kind, :value, :reason, :created_by, :expires_at, :created_at)
             ON DUPLICATE KEY UPDATE reason = VALUES(reason), expires_at = VALUES(expires_at), created_at = VALUES(created_at)'
        );
        $stmt->execute([
            ':kind'       => $ban->kind,
            ':value'      => $ban->value,
            ':reason'     => $ban->reason,
            ':created_by' => $ban->createdBy,
            ':expires_at' => $ban->expiresAt?->format('Y-m-d H:i:s'),
            ':created_at' => $ban->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function listActive(int $limit = 100, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $stmt = $this->pdo->prepare(
            'SELECT * FROM bans WHERE expires_at IS NULL OR expires_at > :now ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':now', $now->format('Y-m-d H:i:s'));
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn (array $row): Ban => $this->hydrate($row), $stmt->fetchAll());
    }

    public function removeById(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM bans WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function hydrate(array $row): Ban
    {
        return new Ban(
            id: (int) $row['id'],
            kind: $row['kind'],
            value: $row['value'],
            reason: $row['reason'],
            createdBy: $row['created_by'],
            expiresAt: $row['expires_at'] !== null ? new DateTimeImmutable($row['expires_at']) : null,
            createdAt: new DateTimeImmutable($row['created_at']),
        );
    }
}
