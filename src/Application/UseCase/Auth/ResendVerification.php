<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Port\Logger;
use App\Application\Service\VerificationMailSender;
use App\Domain\Repository\UserRepository;
use App\Domain\ValueObject\Email;
use DateTimeImmutable;

/**
 * 確認メールの再送。未確認ユーザーにのみ新しいトークンを発行して送り直す。
 *
 * メールアドレス列挙を防ぐため、アドレスの有無・確認状態にかかわらず常に正常終了する
 * （呼び出し側は結果を分岐させず、一律の案内を表示する）。
 */
final class ResendVerification
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly VerificationMailSender $verificationMail,
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
        // 存在し、かつ未確認のときだけ送る。それ以外は何もしない（同じ応答に見せる）。
        if ($user !== null && !$user->isEmailVerified()) {
            try {
                $this->verificationMail->send($user, $now);
            } catch (\Throwable $e) {
                $this->logger?->error('verification_resend_failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
