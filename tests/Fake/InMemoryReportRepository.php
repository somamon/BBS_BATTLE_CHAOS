<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\Report;
use App\Domain\Repository\ReportRepository;

final class InMemoryReportRepository implements ReportRepository
{
    /** @var array<string, Report> */
    public array $reports = [];

    public function insert(Report $report): void
    {
        $this->reports[$report->id] = $report;
    }

    public function listOpen(int $limit = 100): array
    {
        $open = array_values(array_filter($this->reports, static fn (Report $r): bool => $r->status === 'open'));
        usort($open, static fn (Report $a, Report $b): int => $b->createdAt <=> $a->createdAt);
        return array_slice($open, 0, $limit);
    }

    public function setStatus(string $id, string $status): void
    {
        $r = $this->reports[$id] ?? null;
        if ($r !== null) {
            $this->reports[$id] = new Report($r->id, $r->targetType, $r->targetId, $r->reason, $r->detail, $r->reporterId, $r->reporterIp, $status, $r->createdAt);
        }
    }

    public function countOpen(): int
    {
        return count(array_filter($this->reports, static fn (Report $r): bool => $r->status === 'open'));
    }
}
