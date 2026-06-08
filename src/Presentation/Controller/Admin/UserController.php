<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Application\UseCase\Admin\BanUser;
use App\Application\UseCase\Admin\SuspendUser;
use App\Domain\Repository\BanRepository;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * ユーザー管理（一覧・凍結・解除）。到達は AdminMiddleware で admin に限定済み。
 */
final class UserController
{
    use RendersAdmin;

    private const PER_PAGE = 50;

    public function __construct(
        private readonly UserRepository $users,
        private readonly SuspendUser $suspendUser,
        private readonly BanUser $banUser,
        private readonly BanRepository $bans,
        private readonly Auth $auth,
    ) {}

    /** GET /admin/users */
    public function index(Request $request): Response
    {
        // 有効なユーザーBANの集合（バッジ・操作分岐用）。
        $bannedIds = [];
        foreach ($this->bans->listActive(500) as $b) {
            if ($b->kind === 'user') {
                $bannedIds[$b->value] = true;
            }
        }

        $rows = [];
        foreach ($this->users->recentHumans(self::PER_PAGE) as $u) {
            $rows[] = [
                'id'        => $u->id,
                'name'      => $u->name,
                'email'     => $u->email,
                'role'      => $u->role(),
                'status'    => $u->status(),
                'banned'    => isset($bannedIds[$u->id]),
                'money'     => $u->money(),
                'createdAt' => $u->createdAt->format('Y-m-d H:i'),
            ];
        }

        return $this->adminPage('users', 'ユーザー', 'Admin/users', [
            'users' => $rows,
            'flash' => Flash::pull(),
        ]);
    }

    /** POST /admin/users/{id}/ban（days=0で無期限） */
    public function ban(Request $request): Response
    {
        $days = (int) $request->input('days', 0);
        $expiresAt = $days > 0 ? new \DateTimeImmutable('+' . $days . ' days') : null;
        $ok = $this->banUser->ban((string) $this->auth->userId(), (string) $request->param('id'), null, $request->ip(), $expiresAt);
        Flash::set($ok ? 'ユーザーをBANしました。' : '対象が見つかりませんでした。');
        return Response::redirect('/admin/users');
    }

    /** POST /admin/users/{id}/unban */
    public function unban(Request $request): Response
    {
        $ok = $this->banUser->unban((string) $this->auth->userId(), (string) $request->param('id'), $request->ip());
        Flash::set($ok ? 'BANを解除しました。' : '対象が見つかりませんでした。');
        return Response::redirect('/admin/users');
    }

    /** POST /admin/users/{id}/suspend */
    public function suspend(Request $request): Response
    {
        $ok = $this->suspendUser->suspend(
            (string) $this->auth->userId(),
            (string) $request->param('id'),
            $request->ip(),
        );
        Flash::set($ok ? 'ユーザーを凍結しました。' : '対象が見つかりませんでした。');
        return Response::redirect('/admin/users');
    }

    /** POST /admin/users/{id}/unsuspend */
    public function unsuspend(Request $request): Response
    {
        $ok = $this->suspendUser->unsuspend(
            (string) $this->auth->userId(),
            (string) $request->param('id'),
            $request->ip(),
        );
        Flash::set($ok ? '凍結を解除しました。' : '対象が見つかりませんでした。');
        return Response::redirect('/admin/users');
    }
}
