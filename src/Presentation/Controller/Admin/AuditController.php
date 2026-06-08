<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Domain\Repository\AuditLogRepository;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * 監査ログ閲覧（管理操作の履歴）。
 */
final class AuditController
{
    use RendersAdmin;

    public function __construct(
        private readonly AuditLogRepository $logs,
    ) {}

    /** GET /admin/audit?admin=&action= */
    public function index(Request $request): Response
    {
        $admin  = trim((string) $request->query('admin', ''));
        $action = trim((string) $request->query('action', ''));

        return $this->adminPage('audit', '監査ログ', 'Admin/audit', [
            'logs'         => $this->logs->search($admin !== '' ? $admin : null, $action !== '' ? $action : null, 200),
            'filterAdmin'  => $admin,
            'filterAction' => $action,
        ]);
    }
}
