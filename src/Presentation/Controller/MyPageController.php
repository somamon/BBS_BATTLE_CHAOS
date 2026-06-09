<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\User\MyPageQuery;
use App\Application\UseCase\User\UpdateDisplayName;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
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
        private readonly UpdateDisplayName $updateDisplayName,
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

        $me = $this->users->findById($uid);

        $html = $this->page($this->market, $this->auth, $this->users, t('me.title'), 'me', [
            'name'       => $me?->name ?? '',
            'money'      => $data['money'],
            'shareValue' => $data['shareValue'],
            'total'      => $data['total'],
            'holdings'   => $data['holdings'],
            'flash'      => Flash::pull(),
        ]);
        return Response::html($html);
    }

    /** POST /me/name 表示名の変更 */
    public function updateName(Request $request): Response
    {
        $uid = $this->auth->userId();
        if ($uid === null) {
            return Response::redirect('/login');
        }

        try {
            $this->updateDisplayName->execute($uid, (string) $request->input('name', ''));
            Flash::set(t('me.name_changed'));
        } catch (ValidationException $e) {
            Flash::set(t($e->messageKey));
        }
        return Response::redirect('/me');
    }
}
