<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use DateTimeImmutable;

/**
 * NPC投資シミュレーションの遅延tick状態（最終tick時刻）の永続化。
 */
interface BotSimStateRepository
{
    public function getLastTick(): DateTimeImmutable;

    public function setLastTick(DateTimeImmutable $at): void;
}
