<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

/**
 * ラウンド（M2）。1回の「シーズンの始まり〜終局」を表す。番号 = id。
 * 終局すると endedAt と reason（time_up / all_dead / manual）が確定し、
 * 最終ランキングが {@see Round} 単位で保存される。
 */
final class Round
{
    public function __construct(
        public readonly ?int $id,
        public readonly DateTimeImmutable $startedAt,
        public readonly ?DateTimeImmutable $endedAt = null,
        public readonly ?string $reason = null,
    ) {}

    /** 進行中（未終局）か。 */
    public function isActive(): bool
    {
        return $this->endedAt === null;
    }
}
