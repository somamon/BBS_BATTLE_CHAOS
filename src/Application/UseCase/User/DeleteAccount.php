<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Port\TransactionManager;
use App\Domain\Repository\HoldingRepository;
use App\Domain\Repository\InvestmentRepository;
use App\Domain\Repository\UserRepository;

/**
 * 退会（アカウント削除）。本人に紐づくデータを削除する（M5: データ削除の権利）。
 *
 * 削除範囲:
 *  - 投資監査ログ（investments）／保有株（holdings）／本人ユーザー行
 *  - メール確認・パスワード再設定トークンは users の FK CASCADE で連鎖削除される
 *  - 匿名で書いたスレ/レスは creator_id / author_id が SET NULL となり、内容は匿名として残る
 *    （他ユーザーの株・スレッドの一貫性を壊さないため。本人特定情報のみ除去する）
 *
 * 原子性のため全削除を1トランザクションで行う。
 */
final class DeleteAccount
{
    public function __construct(
        private readonly TransactionManager $tx,
        private readonly UserRepository $users,
        private readonly InvestmentRepository $investments,
        private readonly HoldingRepository $holdings,
    ) {}

    /** @return bool 削除したら true（対象が無ければ false） */
    public function execute(string $userId): bool
    {
        return $this->tx->run(function () use ($userId): bool {
            $user = $this->users->findById($userId);
            if ($user === null) {
                return false;
            }

            // investments は FK が RESTRICT のため、ユーザー削除より先に明示的に消す。
            $this->investments->deleteForUser($userId);
            $this->holdings->deleteForUser($userId);
            $this->users->delete($userId);

            return true;
        });
    }
}
