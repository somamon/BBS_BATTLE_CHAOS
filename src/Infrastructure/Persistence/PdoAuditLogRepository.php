<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repository\AuditLogRepository;
use PDO;

final class PdoAuditLogRepository implements AuditLogRepository
{
    public function __construct(private PDO $pdo) {}

    public function recent(int $limit = 200): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admin_audit_logs ORDER BY id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn (array $row): array => [
            'id'         => (int) $row['id'],
            'adminId'    => $row['admin_id'],
            'action'     => $row['action'],
            'targetType' => $row['target_type'],
            'targetId'   => $row['target_id'],
            'detail'     => $row['detail'],
            'ip'         => $row['ip'],
            'createdAt'  => $row['created_at'],
        ], $stmt->fetchAll());
    }
}
