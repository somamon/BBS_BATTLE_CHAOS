<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Support\Ulid;
use DateTimeImmutable;

/**
 * 投資1回の監査ログ。配分の内訳（HP/配当/sink）を記録する追記専用レコード。
 */
final class Investment
{
    public function __construct(
        public readonly string $id,
        public readonly string $investorId,
        public readonly string $threadId,
        public readonly int $amount,
        public readonly int $toHp,
        public readonly int $toDividend,
        public readonly int $toSink,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function record(
        string $investorId,
        string $threadId,
        int $amount,
        int $toHp,
        int $toDividend,
        int $toSink,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: Ulid::generate(),
            investorId: $investorId,
            threadId: $threadId,
            amount: $amount,
            toHp: $toHp,
            toDividend: $toDividend,
            toSink: $toSink,
            createdAt: $now,
        );
    }
}
