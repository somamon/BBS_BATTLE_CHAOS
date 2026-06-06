<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Holding;

interface HoldingRepository
{
    public function find(string $userId, string $threadId): ?Holding;

    /** @return Holding[] 指定スレッドの株主一覧（配当分配用）。 */
    public function findByThread(string $threadId): array;

    /** @return Holding[] 指定ユーザーの保有株一覧（マイページ用）。 */
    public function findByUser(string $userId): array;

    /** 新規・既存どちらも保存（UPSERT）。 */
    public function save(Holding $holding): void;
}
