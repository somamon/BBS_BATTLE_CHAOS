<?php

declare(strict_types=1);

namespace App\Application\Port;

/**
 * 管理操作の監査ログ（追記専用）。Application 層が依存する抽象。
 * 「誰が・何を・どの対象に・補足・どのIPから」を1件記録する。
 */
interface AuditLogger
{
    public function record(
        string $adminId,
        string $action,
        ?string $targetType = null,
        ?string $targetId = null,
        ?string $detail = null,
        ?string $ip = null,
    ): void;
}
