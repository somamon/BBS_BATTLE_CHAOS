<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Exception\InvestException;
use App\Application\UseCase\Invest\InvestInPost;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class InvestController
{
    private const LEVEL_LABELS = ['新規', '注目', '人気', '殿堂入り'];

    public function __construct(
        private readonly InvestInPost $invest,
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
            Flash::set('投資できませんでした: ' . $e->getMessage());
            return Response::redirect($back);
        }

        $label = self::LEVEL_LABELS[$result->levelAfter] ?? '新規';
        $msg = sprintf(
            '%d を投資 → %d株を取得（株価¥%s）。HP回復 %d。投稿HP: %d%s',
            $result->amount,
            $result->shares,
            number_format($result->price, 2),
            $result->toHp,
            $result->postHpAfter,
            $result->leveledUp ? sprintf('／「%s」へ進化！', $label) : '',
        );
        Flash::set($msg);

        return Response::redirect($back);
    }
}
