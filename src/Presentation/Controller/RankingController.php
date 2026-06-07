<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Ranking\RankingQuery;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class RankingController
{
    use RendersLayout;

    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly RankingQuery $ranking,
    ) {}

    /** GET /ranking 総資産ランキング */
    public function index(Request $request): Response
    {
        $html = $this->page($this->market, $this->auth, $this->users, t('ranking.title'), 'ranking', [
            'rows' => $this->ranking->execute(),
        ]);
        return Response::html($html);
    }
}
