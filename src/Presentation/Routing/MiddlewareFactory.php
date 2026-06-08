<?php

declare(strict_types=1);

namespace App\Presentation\Routing;

use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Routing\Middleware\AdminMiddleware;
use App\Presentation\Routing\Middleware\AuthMiddleware;
use App\Presentation\Routing\Middleware\CsrfMiddleware;
use App\Presentation\Routing\Middleware\MiddlewareInterface;

final class MiddlewareFactory
{
    /**
     * @param (callable(class-string):object)|null $resolver
     *        DI が必要なミドルウェア（admin 等）のための依存解決。Router の resolver を渡す。
     */
    public static function make(string $name, ?callable $resolver = null): MiddlewareInterface
    {
        // "rate_limit:post" のように ":" 引数を含む場合は分解
        [$key, $arg] = array_pad(explode(':', $name, 2), 2, null);

        return match ($key) {
            'csrf'       => new CsrfMiddleware(),
            'auth'       => new AuthMiddleware(),
            'admin'      => self::admin($resolver),
            // 'rate_limit' => new RateLimitMiddleware($arg),  // 実装時に追加
            default      => throw new \InvalidArgumentException("unknown middleware: {$name}"),
        };
    }

    /** admin はDB(role)確認のため依存解決が必須。 */
    private static function admin(?callable $resolver): AdminMiddleware
    {
        if ($resolver === null) {
            throw new \RuntimeException('admin middleware requires a container resolver');
        }
        /** @var Auth $auth */
        $auth = $resolver(Auth::class);
        /** @var UserRepository $users */
        $users = $resolver(UserRepository::class);
        return new AdminMiddleware($auth, $users);
    }
}