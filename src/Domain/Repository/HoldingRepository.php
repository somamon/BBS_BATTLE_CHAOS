<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Holding;

interface HoldingRepository
{
    public function find(string $userId, string $postId): ?Holding;

    /** @return Holding[] 指定ユーザーの保有株一覧（マイページ用）。 */
    public function findByUser(string $userId): array;

    /** @return Holding[] 全保有株（ランキング一括集計用。N+1回避）。 */
    public function all(): array;

    /** 新規・既存どちらも保存（UPSERT）。 */
    public function save(Holding $holding): void;

    /** 指定ユーザーの保有株をすべて削除する（退会時のデータ削除）。 */
    public function deleteForUser(string $userId): void;
}
