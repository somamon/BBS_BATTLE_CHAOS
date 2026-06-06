<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Exception\InvestException;
use App\Application\UseCase\Invest\InvestInThread;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class InvestController
{
    public function __construct(
        private readonly InvestInThread $invest,
        private readonly Auth $auth,
    ) {}

    /** POST /thread/{id}/invest 投資（auth ミドルウェアで保護） */
    public function invest(Request $request): Response
    {
        $threadId = (string) $request->param('id');
        $userId   = $this->auth->userId();
        if ($userId === null) {
            return Response::redirect('/login');
        }

        $amount = (int) $request->input('amount', 0);

        try {
            $result = $this->invest->execute($userId, $threadId, $amount);
        } catch (InvestException $e) {
            return Response::error(422, $e->getMessage());
        }

        $msg = sprintf(
            '%d を投資しました（HP回復 %d / 配当 %d / 消滅 %d）。スレHP: %d%s',
            $result->amount,
            $result->toHp,
            $result->toDividend,
            $result->toSink,
            $result->threadHpAfter,
            $result->mutated ? ' / 変異Lv' . $result->mutationLevelAfter . 'へ進化！' : '',
        );
        Flash::set($msg);

        return Response::redirect('/thread/' . $threadId);
    }
}
