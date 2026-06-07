<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Port\Logger;
use App\Application\Service\PasswordResetMailSender;
use App\Domain\Repository\UserRepository;
use App\Domain\ValueObject\Email;
use DateTimeImmutable;

/**
 * パスワード再設定の申請。該当アドレスのユーザーが存在すれば再設定リンクを送る。
 *
 * メールアドレス列挙を防ぐため、アドレスの有無にかかわらず常に正常終了する
 * （呼び出し側は結果を分岐させず、一律の案内を表示する）。ResendVerification と同じ方針。
 *
 * 未確認アカウントにも送る：パスワードを忘れて確認も終えていないケースを救済する。
 */
final class RequestPasswordReset
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordResetMailSender $resetMail,
        private readonly ?Logger $logger = null,
    ) {}

    public function execute(string $emailRaw, ?DateTimeImmutable $now = null): void
    {
        $now ??= new DateTimeImmutable();

        // 形式不正でも沈黙（列挙防止）。
        try {
            $email = Email::fromString($emailRaw);
        } catch (\Throwable) {
            return;
        }

        $user = $this->users->findByEmail($email->value);
        // 存在するときだけ送る。NPC（is_bot）には送らない。
        if ($user !== null && !$user->isBot) {
            try {
                $this->resetMail->send($user, $now);
            } catch (\Throwable $e) {
                $this->logger?->error('password_reset_mail_failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
