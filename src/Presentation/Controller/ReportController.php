<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Port\RateLimiter;
use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Report\SubmitReport;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * 通報フォーム（公開側）。投稿/スレを通報する。
 */
final class ReportController
{
    use RendersLayout;

    private const MAX    = 10;
    private const WINDOW = 3600;

    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly SubmitReport $submitReport,
        private readonly RateLimiter $rateLimiter,
    ) {}

    /** GET /report?type=post|thread&id=... */
    public function form(Request $request): Response
    {
        $type = (string) $request->query('type', '');
        $id   = (string) $request->query('id', '');
        if (!in_array($type, ['post', 'thread'], true) || $id === '') {
            return Response::error(404, t('err.invest_not_found'));
        }
        return Response::html($this->view(null, $type, $id));
    }

    /** POST /report */
    public function submit(Request $request): Response
    {
        $type   = (string) $request->input('type', '');
        $id     = (string) $request->input('id', '');
        $reason = (string) $request->input('reason', '');
        $detail = (string) $request->input('detail', '');

        $key = 'report:' . $request->ip();
        if ($this->rateLimiter->tooManyAttempts($key, self::MAX)) {
            return Response::html($this->view(t('err.too_many_attempts'), $type, $id), 429);
        }
        $this->rateLimiter->hit($key, self::WINDOW);

        try {
            $this->submitReport->execute($type, $id, $reason, $detail, $this->auth->userId(), hash('sha256', $request->ip()));
        } catch (ValidationException $e) {
            return Response::html($this->view(t($e->messageKey), $type, $id), 422);
        }

        $html = $this->page($this->market, $this->auth, $this->users, t('report.done.title'), 'Report/done', []);
        return Response::html($html);
    }

    private function view(?string $error, string $type, string $id): string
    {
        return $this->page($this->market, $this->auth, $this->users, t('report.title'), 'Report/form', [
            'error' => $error,
            'type'  => $type,
            'id'    => $id,
        ]);
    }
}
