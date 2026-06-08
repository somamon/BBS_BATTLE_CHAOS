<?php

declare(strict_types=1);

namespace App\Presentation\Routing\Middleware;

use App\Application\Port\RateLimiter;
use App\Domain\Repository\ContactMessageRepository;
use App\Domain\Repository\ReportRepository;
use App\Domain\Repository\UserRepository;
use App\Infrastructure\Runtime\AdminBadges;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * 管理画面ゲート。ログイン済み かつ role=admin のみ通す。
 * 未認可は 404（存在を隠す）。ロールは毎リクエスト DB 確認。
 *
 * 単一通過点として、ここで（1）ナビの未対応バッジ件数の供給、
 * （2）管理操作（POST）のレート制限 をまとめて行う。
 */
final class AdminMiddleware implements MiddlewareInterface
{
    /** 管理操作（POST）の上限：1管理者あたり1分に60回。 */
    private const ACTION_MAX    = 60;
    private const ACTION_WINDOW = 60;

    public function __construct(
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly RateLimiter $rateLimiter,
        private readonly ReportRepository $reports,
        private readonly ContactMessageRepository $contact,
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

        // 管理操作（変更系=POST）のレート制限（暴走・乗っ取り時の被害抑制）。
        if ($request->method() === 'POST') {
            $key = 'admin_action:' . $uid;
            if ($this->rateLimiter->tooManyAttempts($key, self::ACTION_MAX)) {
                return Response::error(429, '管理操作が多すぎます。しばらく待ってから操作してください。');
            }
            $this->rateLimiter->hit($key, self::ACTION_WINDOW);
        }

        // ナビ用の未対応件数（軽量なCOUNT）。
        try {
            AdminBadges::set($this->reports->countOpen(), $this->contact->countOpen());
        } catch (\Throwable) {
            // 集計失敗時はバッジ無しで継続。
        }

        return $next($request);
    }
}
