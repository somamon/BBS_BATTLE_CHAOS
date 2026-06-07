<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Support\Ulid;
use DateTimeImmutable;

/**
 * 投資1回の監査ログ（追記専用）。約定した株数・株価と配分内訳（株取得/HP回復）を記録する。
 */
final class Investment
{
    public function __construct(
        public readonly string $id,
        public readonly string $investorId,
        public readonly string $postId,
        public readonly int $amount,
        public readonly int $shares,
        public readonly float $price,
        public readonly int $toShares,
        public readonly int $toHp,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function record(
        string $investorId,
        string $postId,
        int $amount,
        int $shares,
        float $price,
        int $toShares,
        int $toHp,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: Ulid::generate(),
            investorId: $investorId,
            postId: $postId,
            amount: $amount,
            shares: $shares,
            price: $price,
            toShares: $toShares,
            toHp: $toHp,
            createdAt: $now,
        );
    }
}
