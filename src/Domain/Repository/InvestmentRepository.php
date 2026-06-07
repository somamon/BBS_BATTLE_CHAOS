<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Investment;

interface InvestmentRepository
{
    public function insert(Investment $investment): void;

    /** 指定ユーザーの投資監査ログを削除する（退会時のデータ削除）。 */
    public function deleteForUser(string $userId): void;
}
