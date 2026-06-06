<?php

declare(strict_types=1);

namespace App\Presentation\Routing\Middleware;

use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // GET等は検証不要。POST系のみ検証
        if ($request->method() === 'POST') {
            $sent    = (string) $request->input('_csrf', '');
            $session = (string) ($_SESSION['_csrf'] ?? '');
            // hash_equals: タイミング攻撃に強い比較
            if ($session === '' || !hash_equals($session, $sent)) {
                return Response::error(403, '不正なリクエストです');
            }
        }
        return $next($request);   // 通過
    }
}