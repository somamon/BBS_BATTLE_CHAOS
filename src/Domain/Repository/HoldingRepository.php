<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Holding;

interface HoldingRepository
{
    public function find(string $userId, string $postId): ?Holding;

    /** @return Holding[] 指定ユーザーの保有株一覧（マイページ・ランキング用）。 */
    public function findByUser(string $userId): array;

    /** 新規・既存どちらも保存（UPSERT）。 */
    public function save(Holding $holding): void;
}
