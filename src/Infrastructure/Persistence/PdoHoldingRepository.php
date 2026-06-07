<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Holding;
use App\Domain\Repository\HoldingRepository;
use PDO;

final class PdoHoldingRepository implements HoldingRepository
{
    public function __construct(private PDO $pdo) {}

    public function find(string $userId, string $postId): ?Holding
    {
        $stmt = $this->pdo->prepare('SELECT * FROM holdings WHERE user_id = ? AND post_id = ?');
        $stmt->execute([$userId, $postId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
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
            'INSERT INTO holdings (user_id, post_id, shares, cost)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE shares = VALUES(shares), cost = VALUES(cost)'
        );
        $stmt->execute([
            $holding->userId,
            $holding->postId,
            $holding->shares(),
            $holding->cost(),
        ]);
    }

    private function hydrate(array $row): Holding
    {
        return new Holding(
            userId: $row['user_id'],
            postId: $row['post_id'],
            shares: (int) $row['shares'],
            cost:   (int) $row['cost'],
        );
    }
}
