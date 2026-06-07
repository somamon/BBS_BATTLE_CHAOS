<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Service\MarketPhaseService;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class HomeController
{
    use RendersLayout;

    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly Auth $auth,
        private readonly UserRepository $users,
    ) {}

    /** GET / トップ（サイト概要・遊び方） */
    public function index(Request $request): Response
    {
        $html = $this->page($this->market, $this->auth, $this->users, 'BBS BATTLE CHAOS', 'home', [
            'isLogin' => $this->auth->check(),
        ]);
        return Response::html($html);
    }
}
