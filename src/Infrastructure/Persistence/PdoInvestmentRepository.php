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
                (id, investor_id, thread_id, amount, to_hp, to_dividend, to_sink, created_at)
             VALUES
                (:id, :investor_id, :thread_id, :amount, :to_hp, :to_dividend, :to_sink, :created_at)'
        );
        $stmt->execute([
            ':id'          => $investment->id,
            ':investor_id' => $investment->investorId,
            ':thread_id'   => $investment->threadId,
            ':amount'      => $investment->amount,
            ':to_hp'       => $investment->toHp,
            ':to_dividend' => $investment->toDividend,
            ':to_sink'     => $investment->toSink,
            ':created_at'  => $investment->createdAt->format('Y-m-d H:i:s'),
        ]);
    }
}
