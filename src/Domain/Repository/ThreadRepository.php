<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Thread;

interface ThreadRepository
{
    /** alive のスレッドを新着順で返す。 */
    /** @return Thread[] */
    public function findAlive(int $limit = 50): array;

    public function findById(string $id): ?Thread;

    /** 行ロック付きで取得（投資トランザクション用）。 */
    public function findByIdForUpdate(string $id): ?Thread;

    public function insert(Thread $thread): void;

    public function save(Thread $thread): void;
}
