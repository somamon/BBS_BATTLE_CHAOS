<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit;

use App\Application\Port\AuditLogger;
use App\Application\Port\Logger;
use DateTimeImmutable;
use PDO;

/**
 * 監査ログの実装。admin_audit_logs へ追記し、同時に構造化ログ（event=admin_action）にも出す。
 * DB側は改ざん検知のため追記専用（更新・削除しない）。
 */
final class PdoAuditLogger implements AuditLogger
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ?Logger $logger = null,
    ) {}

    public function record(
        string $adminId,
        string $action,
        ?string $targetType = null,
        ?string $targetId = null,
        ?string $detail = null,
        ?string $ip = null,
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_audit_logs (admin_id, action, target_type, target_id, detail, ip, created_at)
             VALUES (:admin_id, :action, :target_type, :target_id, :detail, :ip, :created_at)'
        );
        $stmt->execute([
            ':admin_id'    => $adminId,
            ':action'      => $action,
            ':target_type' => $targetType,
            ':target_id'   => $targetId,
            ':detail'      => $detail,
            ':ip'          => $ip,
            ':created_at'  => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        $this->logger?->event('admin_action', [
            'admin_id'    => $adminId,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
        ]);
    }
}
