<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Application\UseCase\Admin\AdminStats;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * 管理ダッシュボード。到達は AdminMiddleware で admin に限定済み。
 */
final class DashboardController
{
    use RendersAdmin;

    public function __construct(
        private readonly AdminStats $stats,
    ) {}

    /** GET /admin */
    public function index(Request $request): Response
    {
        return $this->adminPage('dashboard', 'ダッシュボード', 'Admin/dashboard', [
            'stats' => $this->stats->execute(),
        ]);
    }
}
