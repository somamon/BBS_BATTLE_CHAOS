<?php

declare(strict_types=1);

namespace App\Presentation\Routing\Middleware;

use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

interface MiddlewareInterface
{
    /** 通すなら $next($request) を返す。止めるなら自前の Response を返す。 */
    public function handle(Request $request, callable $next): Response;
}