<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\User;

interface UserRepository
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    /** Google の sub（OIDCの一意ID）でユーザーを引く。連携済みでなければ null。 */
    public function findByGoogleSub(string $googleSub): ?User;

    public function existsByEmail(string $email): bool;

    public function insert(User $user): void;

    public function save(User $user): void;

    /** ユーザーを削除する（退会）。関連行は FK の CASCADE / SET NULL に従う。 */
    public function delete(string $userId): void;

    /** @return User[] 全ユーザー（ランキング集計用）。 */
    public function all(): array;

    /** 人間ユーザー（is_bot=0）の人数。NPC稼働の可否判定に使う。 */
    public function countHumans(): int;

    /** @return User[] 人間ユーザーを新しい順に返す（管理画面の一覧用）。 */
    public function recentHumans(int $limit = 50, int $offset = 0): array;

    /** @return User[] ボットユーザー（is_bot=1）一覧。 */
    public function bots(): array;
}
