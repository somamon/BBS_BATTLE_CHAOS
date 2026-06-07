<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Endgame\EndgameStatus;
use App\Application\UseCase\Ranking\RankingQuery;
use App\Domain\Repository\RoundRepository;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class ResultController
{
    use RendersLayout;

    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly EndgameStatus $endgame,
        private readonly RankingQuery $ranking,
        private readonly RoundRepository $rounds,
    ) {}

    /** GET /result 終局判定 + 現ラウンドのランキング */
    public function index(Request $request): Response
    {
        $status  = $this->endgame->execute();
        $current = $this->rounds->current();

        $html = $this->page($this->market, $this->auth, $this->users, t('nav.result'), 'result', [
            'over'    => $status['over'],
            'reason'  => $status['reason'],
            'rows'    => $this->ranking->execute(),
            'roundNo' => $current?->id,
        ]);
        return Response::html($html);
    }
}
