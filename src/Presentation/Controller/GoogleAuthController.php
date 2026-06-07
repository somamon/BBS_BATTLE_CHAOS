<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Exception\AuthException;
use App\Application\Port\Logger;
use App\Application\UseCase\Auth\LoginWithGoogle;
use App\Domain\Repository\UserRepository;
use App\Infrastructure\Auth\GoogleOAuth;
use App\Application\Service\MarketPhaseService;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Flash;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * Google アカウントログイン（OAuth/OIDC 認可コードフロー）の入口とコールバック。
 * state / PKCE の code_verifier はセッションに保持し、コールバックで照合する。
 */
final class GoogleAuthController
{
    use RendersLayout;

    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly GoogleOAuth $oauth,
        private readonly LoginWithGoogle $loginWithGoogle,
        private readonly ?Logger $logger = null,
    ) {}

    /** GET /auth/google ：Google の同意画面へリダイレクト */
    public function start(Request $request): Response
    {
        if (!$this->oauth->isConfigured()) {
            return Response::redirect('/login');
        }

        $state    = $this->oauth->generateState();
        $verifier = $this->oauth->generateCodeVerifier();
        $_SESSION['oauth_state']    = $state;
        $_SESSION['oauth_verifier'] = $verifier;

        return Response::redirect($this->oauth->authUrl($state, $verifier));
    }

    /** GET /auth/google/callback ：認可コードを受けてログイン */
    public function callback(Request $request): Response
    {
        if (!$this->oauth->isConfigured()) {
            return Response::redirect('/login');
        }

        // state / verifier はワンタイム。必ず取り出して消す。
        $sessState = $_SESSION['oauth_state'] ?? null;
        $verifier  = $_SESSION['oauth_verifier'] ?? null;
        unset($_SESSION['oauth_state'], $_SESSION['oauth_verifier']);

        // ユーザーが同意をキャンセルした等。静かにログインへ戻す。
        if ($request->query('error') !== null) {
            return Response::redirect('/login');
        }

        $state = (string) $request->query('state', '');
        $code  = (string) $request->query('code', '');

        if (
            $state === '' || !is_string($sessState) || !hash_equals($sessState, $state)
            || $code === '' || !is_string($verifier) || $verifier === ''
        ) {
            return $this->loginError(t('err.google_failed'));
        }

        try {
            $profile = $this->oauth->fetchProfile($code, $verifier);
            $user = $this->loginWithGoogle->execute(
                $profile['sub'],
                $profile['email'],
                $profile['emailVerified'],
                $profile['name'],
            );
        } catch (AuthException $e) {
            return $this->loginError(t($e->key));
        } catch (\Throwable $e) {
            $this->logger?->error('google_login_failed', ['error' => $e->getMessage()]);
            return $this->loginError(t('err.google_failed'));
        }

        $this->auth->login($user->id);
        Flash::set(t('flash.logged_in'));
        return Response::redirect('/threads');
    }

    /** ログイン画面をエラー付きで再表示する（AuthController と同じ見た目）。 */
    private function loginError(string $error): Response
    {
        $html = $this->page($this->market, $this->auth, $this->users, t('login.title'), 'Auth/login', [
            'error'         => $error,
            'email'         => '',
            'googleEnabled' => $this->oauth->isConfigured(),
        ]);
        return Response::html($html, 422);
    }
}
