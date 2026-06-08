<?php

declare(strict_types=1);

namespace App\Presentation\Routing\Middleware;

use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * 管理画面ゲート。ログイン済み かつ role=admin のみ通す。
 * 未認可は 404 を返し、管理画面の存在自体を隠す（403で存在を示唆しない）。
 * ロールは毎リクエスト DB で確認する（剥奪を即時反映）。
 */
final class AdminMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Auth $auth,
        private readonly UserRepository $users,
    ) {}

    public function handle(Request $request, callable $next): Response
    {
        $uid = $this->auth->userId();
        if ($uid === null) {
            return Response::error(404, 'Not Found');
        }
        $user = $this->users->findById($uid);
        if ($user === null || !$user->isAdmin()) {
            return Response::error(404, 'Not Found');
        }
        return $next($request);
    }
}
