<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Exception\AuthException;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;
use App\Domain\ValueObject\Email;

/**
 * ログイン認証。メールでユーザーを引き、パスワードを照合する。
 *
 * - ユーザー列挙対策: 該当メールが無くてもダミーのハッシュ検証を行い、応答時間を一定に保つ。
 * - メール未確認のアカウントはログインを拒否する。
 * - 失敗時のメッセージは常に同一（どちらが間違いか明かさない）。
 */
final class LoginUser
{
    /** 存在しないユーザー用のダミー bcrypt ハッシュ（タイミング均一化に使用）。 */
    private const DUMMY_HASH = '$2y$10$N4L5SZYhox74NHw490rtZ.qr4c.zaN8/aSc/wQhh/XTzl2D7qf25C';

    public function __construct(
        private readonly UserRepository $users,
    ) {}

    public function execute(string $emailRaw, string $password): User
    {
        // 形式不正でも「資格情報が不正」に丸める（列挙・形式漏洩の防止）。
        try {
            $email = Email::fromString($emailRaw);
        } catch (\Throwable) {
            password_verify($password, self::DUMMY_HASH);
            throw AuthException::invalidCredentials();
        }

        $user = $this->users->findByEmail($email->value);

        if ($user === null) {
            password_verify($password, self::DUMMY_HASH); // タイミング均一化
            throw AuthException::invalidCredentials();
        }

        if (!$user->verifyPassword($password)) {
            throw AuthException::invalidCredentials();
        }

        if (!$user->isActive()) {
            throw AuthException::accountSuspended();
        }

        if (!$user->isEmailVerified()) {
            throw AuthException::emailUnverified();
        }

        return $user;
    }
}
