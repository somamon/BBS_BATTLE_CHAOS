<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Exception\InvestException;
use App\Application\UseCase\Invest\InvestInPost;
use App\Application\UseCase\Invest\SellShares;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class InvestController
{
    public function __construct(
        private readonly InvestInPost $invest,
        private readonly SellShares $sellShares,
        private readonly Auth $auth,
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
}
