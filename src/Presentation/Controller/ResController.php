<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Exception\BoardException;
use App\Application\Port\RateLimiter;
use App\Domain\Repository\BanRepository;
use App\Application\UseCase\Thread\PostReply;
use App\Domain\Exception\ValidationException;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class ResController
{
    /** 連投クールダウン：同一IPは最短この秒数あけて投稿。 */
    private const COOLDOWN = 10;
    /** 流量上限：同一IPがウィンドウ内に投稿できる最大数。 */
    private const MAX    = 20;
    private const WINDOW = 600; // 10分

    public function __construct(
        private readonly PostReply $postReply,
        private readonly Auth $auth,
        private readonly RateLimiter $rateLimiter,
        private readonly BanRepository $bans,
    ) {}

    /** POST /thread/{id}/posts レス投稿 */
    public function create(Request $request): Response
    {
        $threadId   = (string) $request->param('id');
        $content    = (string) $request->input('content', '');
        $ip         = $request->ip();
        $authorHash = \App\Infrastructure\Security\IpHash::of($ip);
        $authorId   = $this->auth->userId();

        // IP BAN チェック（匿名投稿の遮断）。
        if ($this->bans->isBanned('ip', $authorHash)) {
            return Response::error(403, t('err.banned'));
        }

        // 連投規制（クールダウン）と流量上限。成功した投稿だけを数える。
        if ($this->rateLimiter->tooManyAttempts('res_cd:' . $ip, 1)) {
            return Response::error(429, t('err.posting_too_fast'));
        }
        if ($this->rateLimiter->tooManyAttempts('res:' . $ip, self::MAX)) {
            return Response::error(429, t('err.too_many_attempts'));
        }

        try {
            $this->postReply->execute($threadId, $authorHash, $authorId, $content);
        } catch (BoardException $e) {
            $status = match ($e->key) {
                'err.thread_not_found' => 404,
                'err.duplicate_post'   => 429,
                default                => 403,
            };
            return Response::error($status, t($e->key));
        } catch (ValidationException $e) {
            return Response::error(422, t($e->messageKey));
        }

        $this->rateLimiter->hit('res_cd:' . $ip, self::COOLDOWN);
        $this->rateLimiter->hit('res:' . $ip, self::WINDOW);

        return Response::redirect('/thread/' . $threadId);
    }
}
