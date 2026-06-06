<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Config\Game;
use DateTimeImmutable;

/**
 * 世界フェーズ（相場天候）。常に1行。全スレの減衰倍率を司る（docs/design/04 §3）。
 */
final class WorldState
{
    public function __construct(
        private string $phase,
        private float $phaseMultiplier,
        private DateTimeImmutable $nextShiftAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    /** 抽選時刻を過ぎているか（遅延遷移の判定）。 */
    public function shouldShift(DateTimeImmutable $now): bool
    {
        return $now >= $this->nextShiftAt;
    }

    /** フェーズを差し替え、次の抽選時刻を設定する。 */
    public function shiftTo(string $phase, DateTimeImmutable $nextShiftAt, DateTimeImmutable $now): void
    {
        $this->phase           = $phase;
        $this->phaseMultiplier = Game::phaseMultiplier($phase);
        $this->nextShiftAt     = $nextShiftAt;
        $this->updatedAt       = $now;
    }

    public function phase(): string { return $this->phase; }
    public function multiplier(): float { return $this->phaseMultiplier; }
    public function nextShiftAt(): DateTimeImmutable { return $this->nextShiftAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
}
