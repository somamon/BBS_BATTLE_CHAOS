<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Application\UseCase\Admin\SuspendUser;
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
        private readonly Auth $auth,
    ) {}

    /** GET /admin/users */
    public function index(Request $request): Response
    {
        $rows = [];
        foreach ($this->users->recentHumans(self::PER_PAGE) as $u) {
            $rows[] = [
                'id'        => $u->id,
                'name'      => $u->name,
                'email'     => $u->email,
                'role'      => $u->role(),
                'status'    => $u->status(),
                'money'     => $u->money(),
                'createdAt' => $u->createdAt->format('Y-m-d H:i'),
            ];
        }

        return $this->adminPage('users', 'ユーザー', 'Admin/users', [
            'users' => $rows,
            'flash' => Flash::pull(),
        ]);
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
