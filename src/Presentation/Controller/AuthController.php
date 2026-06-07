<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Exception\AuthException;
use App\Application\Port\RateLimiter;
use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Auth\LoginUser;
use App\Application\UseCase\Auth\RegisterUser;
use App\Application\UseCase\Auth\ResendVerification;
use App\Application\UseCase\Auth\VerifyEmail;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

final class AuthController
{
    use RendersLayout;

    /** 登録のレート制限：1IPあたり1時間に5回まで。 */
    private const REGISTER_MAX    = 5;
    private const REGISTER_WINDOW = 3600;

    /** ログインのレート制限：1IPあたり15分に10回まで（失敗のみ加算）。 */
    private const LOGIN_MAX    = 10;
    private const LOGIN_WINDOW = 900;

    /** 確認メール再送のレート制限：1IPあたり1時間に5回まで。 */
    private const RESEND_MAX    = 5;
    private const RESEND_WINDOW = 3600;

    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly RegisterUser $registerUser,
        private readonly LoginUser $loginUser,
        private readonly VerifyEmail $verifyEmail,
        private readonly ResendVerification $resendVerification,
        private readonly RateLimiter $rateLimiter,
    ) {}

    /** GET /register 登録フォーム */
    public function registerForm(Request $request): Response
    {
        return Response::html($this->registerView(null, '', ''));
    }

    /** POST /register 登録実行（メール確認まではログインさせない） */
    public function register(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));
        $name  = trim((string) $request->input('name', ''));
        $pass  = (string) $request->input('password', '');

        $key = 'register:' . $request->ip();
        if ($this->rateLimiter->tooManyAttempts($key, self::REGISTER_MAX)) {
            return Response::html($this->registerView(t('err.too_many_attempts'), $email, $name), 429);
        }
        $this->rateLimiter->hit($key, self::REGISTER_WINDOW);

        try {
            // 既存アドレスでも例外にせず成功と同じ応答にする（列挙対策は RegisterUser 側）。
            $this->registerUser->execute($email, $name, $pass);
        } catch (ValidationException $e) {
            return Response::html($this->registerView(t($e->messageKey), $email, $name), 422);
        }

        // 確認メールを送信。リンクを踏むまで本ログインしない。
        return Response::html($this->verifySentView($email));
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

        $key = 'login:' . $request->ip();
        if ($this->rateLimiter->tooManyAttempts($key, self::LOGIN_MAX)) {
            return Response::html($this->loginView(t('err.too_many_attempts'), $email), 429);
        }

        try {
            $user = $this->loginUser->execute($email, $pass);
        } catch (AuthException $e) {
            $this->rateLimiter->hit($key, self::LOGIN_WINDOW); // 失敗回数を加算
            return Response::html($this->loginView(t($e->key), $email), 422);
        }

        $this->rateLimiter->clear($key); // 成功でリセット
        $this->auth->login($user->id);
        return Response::redirect('/threads');
    }

    /** GET /verify?token=... メール確認 */
    public function verify(Request $request): Response
    {
        $token = (string) $request->query('token', '');

        try {
            $user = $this->verifyEmail->execute($token);
        } catch (AuthException $e) {
            $html = $this->page($this->market, $this->auth, $this->users, t('verify_result.title'), 'Auth/verify_result', [
                'message' => t($e->key),
            ]);
            return Response::html($html, 422);
        }

        // 確認完了でそのままログイン。
        $this->auth->login($user->id);
        Flash::set(t('flash.verified'));
        return Response::redirect('/threads');
    }

    /** GET /verify/resend 確認メール再送フォーム */
    public function resendForm(Request $request): Response
    {
        return Response::html($this->resendView(null, ''));
    }

    /** POST /verify/resend 確認メール再送（列挙防止のため結果は一律） */
    public function resend(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));

        $key = 'resend:' . $request->ip();
        if ($this->rateLimiter->tooManyAttempts($key, self::RESEND_MAX)) {
            return Response::html($this->resendView(t('err.too_many_attempts'), $email), 429);
        }
        $this->rateLimiter->hit($key, self::RESEND_WINDOW);

        // 成否・アカウントの有無に関わらず同じ応答（メールアドレス列挙を防ぐ）。
        $this->resendVerification->execute($email);

        $html = $this->page($this->market, $this->auth, $this->users, t('resend_done.title'), 'Auth/resend_done', [
            'email' => $email,
        ]);
        return Response::html($html);
    }

    /** POST /logout ログアウト */
    public function logout(Request $request): Response
    {
        $this->auth->logout();
        return Response::redirect('/threads');
    }

    private function registerView(?string $error, string $email, string $name): string
    {
        return $this->page($this->market, $this->auth, $this->users, t('register.title'), 'Auth/register', [
            'error' => $error,
            'email' => $email,
            'name'  => $name,
        ]);
    }

    private function loginView(?string $error, string $email): string
    {
        return $this->page($this->market, $this->auth, $this->users, t('login.title'), 'Auth/login', [
            'error' => $error,
            'email' => $email,
        ]);
    }

    private function verifySentView(string $email): string
    {
        return $this->page($this->market, $this->auth, $this->users, t('verify_sent.title'), 'Auth/verify_sent', [
            'email' => $email,
        ]);
    }

    private function resendView(?string $error, string $email): string
    {
        return $this->page($this->market, $this->auth, $this->users, t('resend.title'), 'Auth/resend', [
            'error' => $error,
            'email' => $email,
        ]);
    }
}
