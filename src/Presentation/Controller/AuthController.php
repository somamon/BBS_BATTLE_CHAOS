<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Exception\AuthException;
use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Auth\LoginUser;
use App\Application\UseCase\Auth\RegisterUser;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class AuthController
{
    use RendersLayout;

    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly RegisterUser $registerUser,
        private readonly LoginUser $loginUser,
    ) {}

    /** GET /register 登録フォーム */
    public function registerForm(Request $request): Response
    {
        return Response::html($this->registerView(null, '', ''));
    }

    /** POST /register 登録実行 */
    public function register(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));
        $name  = trim((string) $request->input('name', ''));
        $pass  = (string) $request->input('password', '');

        if ($email === '' || $name === '' || $pass === '') {
            return Response::html($this->registerView('すべての項目を入力してください', $email, $name), 422);
        }

        try {
            $user = $this->registerUser->execute($email, $name, $pass);
        } catch (AuthException $e) {
            return Response::html($this->registerView($e->getMessage(), $email, $name), 422);
        }

        $this->auth->login($user->id);
        return Response::redirect('/threads');
    }

    /** GET /login ログインフォーム */
    public function loginForm(Request $request): Response
    {
        return Response::html($this->loginView(null, ''));
    }

    /** POST /login ログイン実行 */
    public function login(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));
        $pass  = (string) $request->input('password', '');

        try {
            $user = $this->loginUser->execute($email, $pass);
        } catch (AuthException $e) {
            return Response::html($this->loginView($e->getMessage(), $email), 422);
        }

        $this->auth->login($user->id);
        return Response::redirect('/threads');
    }

    /** POST /logout ログアウト */
    public function logout(Request $request): Response
    {
        $this->auth->logout();
        return Response::redirect('/threads');
    }

    private function registerView(?string $error, string $email, string $name): string
    {
        return $this->page($this->market, $this->auth, $this->users, '新規登録', 'Auth/register', [
            'error' => $error,
            'email' => $email,
            'name'  => $name,
        ]);
    }

    private function loginView(?string $error, string $email): string
    {
        return $this->page($this->market, $this->auth, $this->users, 'ログイン', 'Auth/login', [
            'error' => $error,
            'email' => $email,
        ]);
    }
}
