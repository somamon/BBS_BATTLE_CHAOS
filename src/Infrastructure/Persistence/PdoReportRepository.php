<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Report;
use App\Domain\Repository\ReportRepository;
use DateTimeImmutable;
use PDO;

final class PdoReportRepository implements ReportRepository
{
    public function __construct(private PDO $pdo) {}

    public function insert(Report $report): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO reports (id, target_type, target_id, reason, detail, reporter_id, reporter_ip, status, created_at)
             VALUES (:id, :target_type, :target_id, :reason, :detail, :reporter_id, :reporter_ip, :status, :created_at)'
        );
        $stmt->execute([
            ':id'          => $report->id,
            ':target_type' => $report->targetType,
            ':target_id'   => $report->targetId,
            ':reason'      => $report->reason,
            ':detail'      => $report->detail,
            ':reporter_id' => $report->reporterId,
            ':reporter_ip' => $report->reporterIp,
            ':status'      => $report->status,
            ':created_at'  => $report->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function listOpen(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM reports WHERE status = 'open' ORDER BY created_at DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn (array $row): Report => $this->hydrate($row), $stmt->fetchAll());
    }

    public function setStatus(string $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE reports SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function countOpen(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'open'")->fetchColumn();
    }

    private function hydrate(array $row): Report
    {
        return new Report(
            id: $row['id'],
            targetType: $row['target_type'],
            targetId: $row['target_id'],
            reason: $row['reason'],
            detail: $row['detail'],
            reporterId: $row['reporter_id'],
            reporterIp: $row['reporter_ip'],
            status: $row['status'],
            createdAt: new DateTimeImmutable($row['created_at']),
        );
    }
}
