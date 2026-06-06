<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\User\MyPageQuery;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class MyPageController
{
    use RendersLayout;

    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly MyPageQuery $myPage,
    ) {}

    /** GET /me マイページ */
    public function index(Request $request): Response
    {
        $uid = $this->auth->userId();
        if ($uid === null) {
            return Response::redirect('/login');
        }

        $data = $this->myPage->execute($uid);
        if ($data === null) {
            return Response::redirect('/login');
        }

        $html = $this->page($this->market, $this->auth, $this->users, 'マイページ', 'me', [
            'money'      => $data['money'],
            'shareValue' => $data['shareValue'],
            'total'      => $data['total'],
            'holdings'   => $data['holdings'],
        ]);
        return Response::html($html);
    }
}
