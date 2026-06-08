<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

/**
 * BAN。kind=ip（IPハッシュ）/ user（ユーザーID）。expiresAt=null は無期限。
 * 主に匿名投稿のIP遮断に使う（ユーザー単位の遮断は凍結=suspendが担う）。
 */
final class Ban
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $kind,
        public readonly string $value,
        public readonly ?string $reason,
        public readonly ?string $createdBy,
        public readonly ?DateTimeImmutable $expiresAt,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(string $kind, string $value, ?string $reason, ?string $createdBy, DateTimeImmutable $now, ?DateTimeImmutable $expiresAt = null): self
    {
        return new self(null, $kind, $value, $reason, $createdBy, $expiresAt, $now);
    }
}
