<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\User\DeleteAccount;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * アカウント管理（退会＝データ削除）。M5。
 */
final class AccountController
{
    use RendersLayout;

    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly DeleteAccount $deleteAccount,
    ) {}

    /** GET /account/delete 退会確認ページ */
    public function confirm(Request $request): Response
    {
        if ($this->auth->userId() === null) {
            return Response::redirect('/login');
        }

        $html = $this->page($this->market, $this->auth, $this->users, t('account.delete.title'), 'Account/delete', []);
        return Response::html($html);
    }

    /** POST /account/delete 退会実行（本人のデータを削除してログアウト） */
    public function delete(Request $request): Response
    {
        $uid = $this->auth->userId();
        if ($uid === null) {
            return Response::redirect('/login');
        }

        $this->deleteAccount->execute($uid);
        $this->auth->logout();
        Flash::set(t('flash.account_deleted'));
        return Response::redirect('/');
    }
}
