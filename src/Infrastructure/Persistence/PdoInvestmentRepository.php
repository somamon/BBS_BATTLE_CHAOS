<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Investment;
use App\Domain\Repository\InvestmentRepository;
use PDO;

final class PdoInvestmentRepository implements InvestmentRepository
{
    public function __construct(private PDO $pdo) {}

    public function insert(Investment $investment): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO investments
                (id, investor_id, post_id, amount, shares, price, to_shares, to_hp, created_at)
             VALUES
                (:id, :investor_id, :post_id, :amount, :shares, :price, :to_shares, :to_hp, :created_at)'
        );
        $stmt->execute([
            ':id'          => $investment->id,
            ':investor_id' => $investment->investorId,
            ':post_id'     => $investment->postId,
            ':amount'      => $investment->amount,
            ':shares'      => $investment->shares,
            ':price'       => $investment->price,
            ':to_shares'   => $investment->toShares,
            ':to_hp'       => $investment->toHp,
            ':created_at'  => $investment->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function deleteForUser(string $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM investments WHERE investor_id = ?');
        $stmt->execute([$userId]);
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM investments')->fetchColumn();
    }
}
