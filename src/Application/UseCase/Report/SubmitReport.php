<?php

declare(strict_types=1);

namespace App\Application\UseCase\Report;

use App\Domain\Entity\Report;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\ReportRepository;
use DateTimeImmutable;

/**
 * 通報の受付（公開側）。対象種別・理由を検証して保存する。
 */
final class SubmitReport
{
    private const TARGET_TYPES = ['post', 'thread'];
    private const DETAIL_MAX = 500;

    public function __construct(
        private readonly ReportRepository $reports,
    ) {}

    public function execute(
        string $targetType,
        string $targetId,
        string $reason,
        ?string $detail,
        ?string $reporterId,
        string $reporterIp,
        ?DateTimeImmutable $now = null,
    ): void {
        $now ??= new DateTimeImmutable();

        if (!in_array($targetType, self::TARGET_TYPES, true) || trim($targetId) === '') {
            throw ValidationException::field('target', 'validation.generic', '通報対象が不正です');
        }
        if (!in_array($reason, Report::REASONS, true)) {
            throw ValidationException::field('reason', 'validation.generic', '理由が不正です');
        }
        $detail = $detail !== null ? trim($detail) : null;
        if ($detail !== null && mb_strlen($detail) > self::DETAIL_MAX) {
            throw ValidationException::field('detail', 'validation.generic', '詳細が長すぎます');
        }

        $this->reports->insert(Report::create($targetType, $targetId, $reason, $detail ?: null, $reporterId, $reporterIp, $now));
    }
}
