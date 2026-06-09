<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Domain\Repository\UserRepository;
use App\Domain\ValueObject\DisplayName;

/**
 * 表示名の変更。DisplayName で検証してからユーザーを更新する。
 * （Googleログインでも本名を強制されず、任意の表示名にできるようにするための窓口。）
 */
final class UpdateDisplayName
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    /** @throws \App\Domain\Exception\ValidationException 表示名が不正なとき */
    public function execute(string $userId, string $rawName): void
    {
        $name = DisplayName::fromString($rawName); // 空/長すぎ/制御文字は例外

        $user = $this->users->findById($userId);
        if ($user === null) {
            return;
        }
        $user->rename($name->value);
        $this->users->save($user);
    }
}
