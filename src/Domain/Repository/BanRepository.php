<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Ban;
use DateTimeImmutable;

interface BanRepository
{
    /** 有効なBANが存在するか（期限切れは無効）。 */
    public function isBanned(string $kind, string $value, ?DateTimeImmutable $now = null): bool;

    public function insert(Ban $ban): void;

    /** @return Ban[] 有効なBANを新しい順に。 */
    public function listActive(int $limit = 100, ?DateTimeImmutable $now = null): array;

    public function removeById(int $id): void;
}
