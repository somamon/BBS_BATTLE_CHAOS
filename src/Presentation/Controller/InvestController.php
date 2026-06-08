<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Exception\InvestException;
use App\Application\Port\RateLimiter;
use App\Application\UseCase\Invest\InvestInPost;
use App\Application\UseCase\Invest\SellShares;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class InvestController
{
    /** 取引(投資/売却)の濫用・DoS対策。userId 単位でクールダウン＋流量上限。 */
    private const TRADE_COOLDOWN = 1;   // 同一ユーザーは最短この秒数あけて取引
    private const TRADE_MAX      = 60;  // ウィンドウ内に許す最大取引回数
    private const TRADE_WINDOW   = 60;  // 集計ウィンドウ（秒）

    public function __construct(
        private readonly InvestInPost $invest,
        private readonly SellShares $sellShares,
        private readonly Auth $auth,
        private readonly RateLimiter $rateLimiter,
    ) {}

    /** POST /post/{id}/invest 投稿への投資（auth ミドルウェアで保護） */
    public function invest(Request $request): Response
    {
        $postId = (string) $request->param('id');
        $userId = $this->auth->userId();
        if ($userId === null) {
            return Response::redirect('/login');
        }

        // 投資後のリダイレクト先（投稿が属するスレ）。フォームの hidden から受け取る。
        $threadId = (string) $request->input('thread_id', '');
        $back     = $threadId !== '' ? '/thread/' . $threadId : '/threads';

        // 取引のレート制限（スクリプトによる大量投資でのロック競合/DB枯渇を防ぐ）。
        if ($this->overTradeLimit($userId)) {
            Flash::set(t('err.too_many_attempts'));
            return Response::redirect($back);
        }

        $amount = (int) $request->input('amount', 0);

        try {
            $result = $this->invest->execute($userId, $postId, $amount);
        } catch (InvestException $e) {
            Flash::set(t('flash.invest_failed', ['msg' => t($e->key)]));
            return Response::redirect($back);
        }

        $leveled = $result->leveledUp
            ? t('flash.invest_leveled', ['label' => t('level.' . $result->levelAfter)])
            : '';
        Flash::set(t('flash.invest', [
            'amount' => number_format($result->amount),
            'shares' => number_format($result->shares),
            'price'  => number_format($result->price, 2),
            'toHp'   => number_format($result->toHp),
            'hp'     => number_format($result->postHpAfter),
            'level'  => $leveled,
        ]));

        return Response::redirect($back);
    }

    /** POST /post/{id}/sell 株の売却（auth ミドルウェアで保護） */
    public function sell(Request $request): Response
    {
        $postId = (string) $request->param('id');
        $userId = $this->auth->userId();
        if ($userId === null) {
            return Response::redirect('/login');
        }

        $threadId = (string) $request->input('thread_id', '');
        $back     = $threadId !== '' ? '/thread/' . $threadId : '/threads';

        // 取引のレート制限（投資と共通の上限。売り叩きの濫用も同じ枠で抑える）。
        if ($this->overTradeLimit($userId)) {
            Flash::set(t('err.too_many_attempts'));
            return Response::redirect($back);
        }

        $shares   = (int) $request->input('shares', 0);

        try {
            $result = $this->sellShares->execute($userId, $postId, $shares);
        } catch (InvestException $e) {
            Flash::set(t('flash.sell_failed', ['msg' => t($e->key)]));
            return Response::redirect($back);
        }

        Flash::set(t('flash.sell', [
            'shares' => number_format($result['shares']),
            'payout' => number_format($result['payout']),
        ]));
        return Response::redirect($back);
    }

    /**
     * 取引(投資/売却)のレート制限。上限到達なら true。
     * 未到達のときは試行を1回加算して false（失敗試行も数えてDoSを抑止する）。
     */
    private function overTradeLimit(string $userId): bool
    {
        if (
            $this->rateLimiter->tooManyAttempts('trade_cd:' . $userId, 1)
            || $this->rateLimiter->tooManyAttempts('trade:' . $userId, self::TRADE_MAX)
        ) {
            return true;
        }
        $this->rateLimiter->hit('trade_cd:' . $userId, self::TRADE_COOLDOWN);
        $this->rateLimiter->hit('trade:' . $userId, self::TRADE_WINDOW);
        return false;
    }
}
