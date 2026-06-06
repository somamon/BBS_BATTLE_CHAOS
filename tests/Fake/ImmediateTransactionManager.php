<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Application\Port\TransactionManager;

/** テスト用：トランザクションを張らずにその場でコールバックを実行する。 */
final class ImmediateTransactionManager implements TransactionManager
{
    public function run(callable $fn): mixed
    {
        return $fn();
    }
}
