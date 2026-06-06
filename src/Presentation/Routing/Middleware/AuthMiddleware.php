<?php

declare(strict_types=1);

namespace App\Presentation\Routing\Middleware;

use App\Presentation\Http\Auth;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * 認証必須ルートを保護する。未ログインなら /login へリダイレクト。
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Auth $auth = new Auth(),
    ) {}

    public function handle(Request $request, callable $next): Response
    {
        if (!$this->auth->check()) {
            return Response::redirect('/login');
        }
        return $next($request);
    }
}
