<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Exception\AuthException;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * 新規ユーザー登録。メール重複を弾き、パスワードをハッシュ化して永続化する。
 */
final class RegisterUser
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    public function execute(string $email, string $name, string $password): User
    {
        if ($this->users->existsByEmail($email)) {
            throw AuthException::emailTaken();
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $user = User::register($email, $name, $passwordHash, new DateTimeImmutable());
        $this->users->insert($user);

        return $user;
    }
}
