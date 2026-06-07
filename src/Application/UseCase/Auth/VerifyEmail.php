<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Exception\AuthException;
use App\Application\Port\TransactionManager;
use App\Domain\Entity\EmailVerification;
use App\Domain\Entity\User;
use App\Domain\Repository\EmailVerificationRepository;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * メール確認。生トークンをハッシュ化して照合し、有効ならユーザーを確認済みにする。
 * 使い終わったトークンは破棄する（使い回し防止）。
 */
final class VerifyEmail
{
    public function __construct(
        private readonly TransactionManager $tx,
        private readonly UserRepository $users,
        private readonly EmailVerificationRepository $verifications,
    ) {}

    /** @return User 確認が完了したユーザー */
    public function execute(string $rawToken, ?DateTimeImmutable $now = null): User
    {
        $now ??= new DateTimeImmutable();

        $rawToken = trim($rawToken);
        if ($rawToken === '') {
            throw AuthException::invalidToken();
        }

        return $this->tx->run(function () use ($rawToken, $now): User {
            $hash = EmailVerification::hashToken($rawToken);
            $verification = $this->verifications->findByTokenHash($hash);

            if ($verification === null || $verification->isExpired($now)) {
                throw AuthException::invalidToken();
            }

            $user = $this->users->findById($verification->userId);
            if ($user === null) {
                throw AuthException::invalidToken();
            }

            $user->markEmailVerified($now);
            $this->users->save($user);
            $this->verifications->deleteForUser($user->id); // 全トークン無効化

            return $user;
        });
    }
}
