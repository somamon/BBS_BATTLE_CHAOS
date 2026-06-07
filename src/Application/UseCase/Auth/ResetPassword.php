<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Exception\AuthException;
use App\Application\Port\TransactionManager;
use App\Domain\Entity\PasswordReset;
use App\Domain\Entity\User;
use App\Domain\Repository\PasswordResetRepository;
use App\Domain\Repository\UserRepository;
use App\Domain\Service\PasswordPolicy;
use DateTimeImmutable;

/**
 * パスワード再設定の実行。生トークンをハッシュ化して照合し、有効なら新パスワードを保存する。
 * 使い終わったトークンは破棄する（使い回し防止）。VerifyEmail と同じ方針。
 *
 * パスワード未確認ユーザーがこのフローを完了した場合、本人がメールを受け取れる＝
 * アドレス所有の証明になるため、ついでにメール確認も完了させる。
 */
final class ResetPassword
{
    public function __construct(
        private readonly TransactionManager $tx,
        private readonly UserRepository $users,
        private readonly PasswordResetRepository $resets,
    ) {}

    /** @return User 再設定が完了したユーザー */
    public function execute(string $rawToken, string $newPasswordRaw, ?DateTimeImmutable $now = null): User
    {
        $now ??= new DateTimeImmutable();

        $rawToken = trim($rawToken);
        if ($rawToken === '') {
            throw AuthException::invalidToken();
        }

        // トークン照合より前にパスワードポリシーを検証（不正な新パスワードならトークンを消費しない）。
        PasswordPolicy::assertValid($newPasswordRaw);
        $passwordHash = password_hash($newPasswordRaw, PASSWORD_DEFAULT);

        return $this->tx->run(function () use ($rawToken, $passwordHash, $now): User {
            $hash  = PasswordReset::hashToken($rawToken);
            $reset = $this->resets->findByTokenHash($hash);

            if ($reset === null || $reset->isExpired($now)) {
                throw AuthException::invalidToken();
            }

            $user = $this->users->findById($reset->userId);
            if ($user === null) {
                throw AuthException::invalidToken();
            }

            $user->changePassword($passwordHash);
            $user->markEmailVerified($now); // 受信できた＝アドレス所有の証明
            $this->users->save($user);
            $this->resets->deleteForUser($user->id); // 全トークン無効化

            return $user;
        });
    }
}
