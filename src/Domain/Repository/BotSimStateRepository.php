<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use DateTimeImmutable;

/**
 * NPC投資シミュレーションの遅延tick状態。
 * 同時アクセス時の二重実行を防ぐため、tick の取得は原子的な「claim」で行う。
 */
interface BotSimStateRepository
{
    /**
     * 前回tickから minIntervalSeconds 以上経過していれば last_tick を now に進めて「占有」し、
     * 直前の last_tick を返す。まだ間隔未満、または他リクエストが占有済みなら null。
     */
    public function tryClaim(DateTimeImmutable $now, int $minIntervalSeconds): ?DateTimeImmutable;
}
