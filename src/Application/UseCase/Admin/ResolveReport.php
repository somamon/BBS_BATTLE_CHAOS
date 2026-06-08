<?php

declare(strict_types=1);

namespace App\Application\UseCase\Admin;

use App\Application\Port\AuditLogger;
use App\Domain\Repository\ReportRepository;

/**
 * 通報の対応（resolved=対応済み / rejected=却下）。操作は監査ログに残す。
 */
final class ResolveReport
{
    private const ALLOWED = ['resolved', 'rejected'];

    public function __construct(
        private readonly ReportRepository $reports,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(string $adminId, string $reportId, string $status, ?string $ip = null): void
    {
        if (!in_array($status, self::ALLOWED, true)) {
            throw new \InvalidArgumentException("invalid status: {$status}");
        }
        $this->reports->setStatus($reportId, $status);
        $this->audit->record($adminId, 'report.' . $status, 'report', $reportId, null, $ip);
    }
}
