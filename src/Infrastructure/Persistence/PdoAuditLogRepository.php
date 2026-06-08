<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repository\AuditLogRepository;
use PDO;

final class PdoAuditLogRepository implements AuditLogRepository
{
    public function __construct(private PDO $pdo) {}

    public function search(?string $adminId, ?string $action, int $limit = 200): array
    {
        $where = [];
        $params = [];
        if ($adminId !== null && $adminId !== '') {
            $where[] = 'admin_id = :admin';
            $params[':admin'] = $adminId;
        }
        if ($action !== null && $action !== '') {
            $where[] = 'action LIKE :action';
            $params[':action'] = '%' . $action . '%';
        }
        $sql = 'SELECT * FROM admin_audit_logs'
            . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY id DESC LIMIT :limit';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
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
