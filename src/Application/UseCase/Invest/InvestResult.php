<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invest;

/** 投資結果の内訳（画面表示・テスト用）。 */
final class InvestResult
{
    public function __construct(
        public readonly int $amount,
        public readonly int $shares,
        public readonly float $price,
        public readonly int $toShares,
        public readonly int $toHp,
        public readonly int $postHpAfter,
        public readonly int $levelAfter,
        public readonly bool $leveledUp,
    ) {}
}
