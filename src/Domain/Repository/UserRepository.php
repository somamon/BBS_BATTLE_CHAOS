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

    /** @return User[] 全ユーザー（ランキング集計用）。 */
    public function all(): array;
}
