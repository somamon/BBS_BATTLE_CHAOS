<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Holding;
use App\Domain\Repository\HoldingRepository;
use PDO;

final class PdoHoldingRepository implements HoldingRepository
{
    public function __construct(private PDO $pdo) {}

    public function find(string $userId, string $threadId): ?Holding
    {
        $stmt = $this->pdo->prepare('SELECT * FROM holdings WHERE user_id = ? AND thread_id = ?');
        $stmt->execute([$userId, $threadId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByThread(string $threadId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM holdings WHERE thread_id = ?');
        $stmt->execute([$threadId]);

        return array_map(
            fn (array $row): Holding => $this->hydrate($row),
            $stmt->fetchAll()
        );
    }

    public function findByUser(string $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM holdings WHERE user_id = ?');
        $stmt->execute([$userId]);

        return array_map(
            fn (array $row): Holding => $this->hydrate($row),
            $stmt->fetchAll()
        );
    }

    public function save(Holding $holding): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO holdings (user_id, thread_id, shares)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE shares = VALUES(shares)'
        );
        $stmt->execute([
            $holding->userId,
            $holding->threadId,
            $holding->shares(),
        ]);
    }

    private function hydrate(array $row): Holding
    {
        return new Holding(
            userId:   $row['user_id'],
            threadId: $row['thread_id'],
            shares:   (int) $row['shares'],
        );
    }
}
