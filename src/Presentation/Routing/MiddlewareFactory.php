<?php

declare(strict_types=1);

namespace App\Presentation\Routing;

use App\Presentation\Routing\Middleware\AuthMiddleware;
use App\Presentation\Routing\Middleware\CsrfMiddleware;
use App\Presentation\Routing\Middleware\MiddlewareInterface;

final class MiddlewareFactory
{
    public static function make(string $name): MiddlewareInterface
    {
        // "rate_limit:post" のように ":" 引数を含む場合は分解
        [$key, $arg] = array_pad(explode(':', $name, 2), 2, null);

        return match ($key) {
            'csrf'       => new CsrfMiddleware(),
            'auth'       => new AuthMiddleware(),
            // 'rate_limit' => new RateLimitMiddleware($arg),  // 実装時に追加
            default      => throw new \InvalidArgumentException("unknown middleware: {$name}"),
        };
    }
}