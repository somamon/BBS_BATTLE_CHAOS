<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Support\Ulid;
use DateTimeImmutable;

/**
 * 通報。公開側から投稿/スレを通報し、管理側で対応（resolved/rejected）する。
 */
final class Report
{
    public const REASONS = ['spam', 'abuse', 'illegal', 'other'];

    public function __construct(
        public readonly string $id,
        public readonly string $targetType,   // post | thread
        public readonly string $targetId,
        public readonly string $reason,
        public readonly ?string $detail,
        public readonly ?string $reporterId,
        public readonly string $reporterIp,   // ハッシュ
        public readonly string $status,       // open | resolved | rejected
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        string $targetType,
        string $targetId,
        string $reason,
        ?string $detail,
        ?string $reporterId,
        string $reporterIp,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: Ulid::generate(),
            targetType: $targetType,
            targetId: $targetId,
            reason: $reason,
            detail: $detail,
            reporterId: $reporterId,
            reporterIp: $reporterIp,
            status: 'open',
            createdAt: $now,
        );
    }
}
