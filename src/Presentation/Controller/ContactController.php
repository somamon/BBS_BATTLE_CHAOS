<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Port\RateLimiter;
use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Contact\SubmitContact;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepository;
use App\Presentation\Http\Auth;
use App\Presentation\Http\Request;
use App\Presentation\Http\Response;

/**
 * お問い合わせフォーム。運営の連絡先メールへ送る。
 * スパム対策: ハニーポット（隠しフィールド）＋ IP単位のレート制限。
 */
final class ContactController
{
    use RendersLayout;

    /** 1IPあたり1時間に5回まで。 */
    private const MAX    = 5;
    private const WINDOW = 3600;

    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly SubmitContact $submitContact,
        private readonly RateLimiter $rateLimiter,
    ) {}

    /** GET /contact 入力フォーム（ログイン中はメール・表示名を初期表示） */
    public function form(Request $request): Response
    {
        $name  = '';
        $email = '';
        $uid   = $this->auth->userId();
        if ($uid !== null) {
            $user = $this->users->findById($uid);
            if ($user !== null) {
                $name  = $user->name;
                $email = $user->email;
            }
        }

        return Response::html($this->view(null, $name, $email, ''));
    }

    /** POST /contact 送信 */
    public function submit(Request $request): Response
    {
        $name    = trim((string) $request->input('name', ''));
        $email   = trim((string) $request->input('email', ''));
        $message = (string) $request->input('message', '');

        // ハニーポット: 人間には見えない項目。埋まっていればボットとみなし、成功画面だけ返す。
        if (trim((string) $request->input('website', '')) !== '') {
            return Response::html($this->doneView());
        }

        $key = 'contact:' . $request->ip();
        if ($this->rateLimiter->tooManyAttempts($key, self::MAX)) {
            return Response::html($this->view(t('err.too_many_attempts'), $name, $email, $message), 429);
        }
        $this->rateLimiter->hit($key, self::WINDOW);

        try {
            $this->submitContact->execute($name, $email, $message, [
                'ip'     => $request->ip(),
                'locale' => current_locale(),
                'userId' => $this->auth->userId(),
            ]);
        } catch (ValidationException $e) {
            return Response::html($this->view(t($e->messageKey), $name, $email, $message), 422);
        }

        return Response::html($this->doneView());
    }

    private function view(?string $error, string $name, string $email, string $message): string
    {
        return $this->page($this->market, $this->auth, $this->users, t('contact.title'), 'Contact/form', [
            'error'   => $error,
            'name'    => $name,
            'email'   => $email,
            'message' => $message,
        ]);
    }

    private function doneView(): string
    {
        return $this->page($this->market, $this->auth, $this->users, t('contact.done.title'), 'Contact/done', []);
    }
}
