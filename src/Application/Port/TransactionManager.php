<?php

declare(strict_types=1);

namespace App\Application\Port;

/**
 * トランザクション境界を表すポート（Application 層が依存する抽象）。
 * 投資ユースケースのように複数リポジトリへの書き込みを原子的に行う際に使う。
 */
interface TransactionManager
{
    /**
     * $fn をトランザクション内で実行し、戻り値を返す。
     * 例外が出たらロールバックして再スロー。
     *
     * @template T
     * @param callable():T $fn
     * @return T
     */
    public function run(callable $fn): mixed;
}
