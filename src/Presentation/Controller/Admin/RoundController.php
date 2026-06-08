<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Application\Port\AuditLogger;
use App\Application\UseCase\Endgame\FinalizeAndResetRound;
use App\Domain\Repository\RoundRepository;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * ラウンド管理（現況・過去ランキング閲覧、強制リセット）。
 * 破壊的操作のため、リセットは実行前にパスワード再認証（step-up）を要求する。
 */
final class RoundController
{
    use RendersAdmin;

    public function __construct(
        private readonly RoundRepository $rounds,
        private readonly FinalizeAndResetRound $finalize,
        private readonly UserRepository $users,
        private readonly AuditLogger $audit,
        private readonly Auth $auth,
    ) {}

    /** GET /admin/rounds */
    public function index(Request $request): Response
    {
        $current = $this->rounds->current();
        $ended   = $this->rounds->latestEnded();
        $rankings = $ended !== null && $ended->id !== null ? $this->rounds->rankings($ended->id) : [];

        return $this->adminPage('rounds', 'ラウンド', 'Admin/rounds', [
            'current'  => $current?->id,
            'ended'    => $ended?->id,
            'reason'   => $ended?->reason,
            'rankings' => $rankings,
            'flash'    => Flash::pull(),
        ]);
    }

    /** POST /admin/rounds/reset 強制リセット（パスワード再認証つき） */
    public function reset(Request $request): Response
    {
        $password = (string) $request->input('password', '');
        $admin = $this->users->findById((string) $this->auth->userId());

        // step-up 再認証：本人パスワードを確認（Google専用アカウントはCLIで実行）。
        if ($admin === null || !$admin->verifyPassword($password)) {
            Flash::set('パスワードが確認できませんでした。リセットは実行されていません。');
            return Response::redirect('/admin/rounds');
        }

        $result = $this->finalize->execute(force: true);
        $this->audit->record(
            $admin->id,
            'round.reset',
            'round',
            (string) ($result['endedRound'] ?? '-'),
            'new=' . ($result['newRound'] ?? '-'),
            $request->ip(),
        );
        Flash::set('ラウンドをリセットしました（新ラウンド #' . ($result['newRound'] ?? '-') . '）。');
        return Response::redirect('/admin/rounds');
    }
}
