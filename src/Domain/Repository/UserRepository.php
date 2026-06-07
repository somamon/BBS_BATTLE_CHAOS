<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\User;

interface UserRepository
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function existsByEmail(string $email): bool;

    public function insert(User $user): void;

    public function save(User $user): void;

    /** ユーザーを削除する（退会）。関連行は FK の CASCADE / SET NULL に従う。 */
    public function delete(string $userId): void;

    /** @return User[] 全ユーザー（ランキング集計用）。 */
    public function all(): array;

    /** 人間ユーザー（is_bot=0）の人数。NPC稼働の可否判定に使う。 */
    public function countHumans(): int;

    /** @return User[] ボットユーザー（is_bot=1）一覧。 */
    public function bots(): array;
}
