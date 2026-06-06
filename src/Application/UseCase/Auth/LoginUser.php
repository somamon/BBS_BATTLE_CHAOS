<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Exception\AuthException;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;

/**
 * ログイン認証。メールでユーザーを引き、パスワードを照合する。
 */
final class LoginUser
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    public function execute(string $email, string $password): User
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || !$user->verifyPassword($password)) {
            throw AuthException::invalidCredentials();
        }

        return $user;
    }
}
