<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Application\UseCase\Admin\ResolveReport;
use App\Domain\Repository\ReportRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * 通報の管理（一覧・対応/却下）。
 */
final class ReportController
{
    use RendersAdmin;

    public function __construct(
        private readonly ReportRepository $reports,
        private readonly ResolveReport $resolveReport,
        private readonly Auth $auth,
    ) {}

    /** GET /admin/reports */
    public function index(Request $request): Response
    {
        $rows = [];
        foreach ($this->reports->listOpen(100) as $r) {
            $rows[] = [
                'id'         => $r->id,
                'targetType' => $r->targetType,
                'targetId'   => $r->targetId,
                'reason'     => $r->reason,
                'detail'     => $r->detail,
                'createdAt'  => $r->createdAt->format('Y-m-d H:i'),
                'link'       => $r->targetType === 'thread'
                    ? '/thread/' . $r->targetId
                    : null, // post は親スレ不明なので直リンクは省略
            ];
        }
        return $this->adminPage('reports', '通報', 'Admin/reports', [
            'reports' => $rows,
            'flash'   => Flash::pull(),
        ]);
    }

    /** POST /admin/reports/{id}/resolve */
    public function resolve(Request $request): Response
    {
        $this->resolveReport->execute((string) $this->auth->userId(), (string) $request->param('id'), 'resolved', $request->ip());
        Flash::set('対応済みにしました。');
        return Response::redirect('/admin/reports');
    }

    /** POST /admin/reports/{id}/reject */
    public function reject(Request $request): Response
    {
        $this->resolveReport->execute((string) $this->auth->userId(), (string) $request->param('id'), 'rejected', $request->ip());
        Flash::set('却下しました。');
        return Response::redirect('/admin/reports');
    }
}
