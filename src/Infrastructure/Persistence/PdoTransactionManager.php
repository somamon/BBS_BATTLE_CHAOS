<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Port\TransactionManager;
use PDO;

/**
 * PDO によるトランザクション境界の実装。
 * リポジトリ群と同じ PDO インスタンスを共有することで原子性を担保する。
 */
final class PdoTransactionManager implements TransactionManager
{
    public function __construct(private readonly PDO $pdo) {}

    public function run(callable $fn): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $fn();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
