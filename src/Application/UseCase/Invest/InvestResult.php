<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invest;

/** 投資結果の内訳（画面表示・テスト用）。 */
final class InvestResult
{
    public function __construct(
        public readonly int $amount,
        public readonly int $toHp,
        public readonly int $toDividend,
        public readonly int $toSink,
        public readonly int $threadHpAfter,
        public readonly int $mutationLevelAfter,
        public readonly bool $mutated,
    ) {}
}
