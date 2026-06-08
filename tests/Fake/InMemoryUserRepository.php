<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $users = [];

    public function findById(string $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function findByIdForUpdate(string $id): ?User
    {
        return $this->findById($id); // インメモリではロック不要
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email === $email) {
                return $user;
            }
        }
        return null;
    }

    public function findByGoogleSub(string $googleSub): ?User
    {
        foreach ($this->users as $user) {
            if ($user->googleSub() === $googleSub) {
                return $user;
            }
        }
        return null;
    }

    public function existsByEmail(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function insert(User $user): void
    {
        $this->users[$user->id] = $user;
    }

    public function save(User $user): void
    {
        $this->users[$user->id] = $user;
    }

    public function delete(string $userId): void
    {
        unset($this->users[$userId]);
    }

    public function all(): array
    {
        return array_values($this->users);
    }

    public function countHumans(): int
    {
        return count(array_filter($this->users, static fn (User $u): bool => !$u->isBot));
    }

    public function recentHumans(int $limit = 50, int $offset = 0): array
    {
        $humans = array_values(array_filter($this->users, static fn (User $u): bool => !$u->isBot));
        // 新しい順（createdAt 降順）。同時刻は不問。
        usort($humans, static fn (User $a, User $b): int => $b->createdAt <=> $a->createdAt);
        return array_slice($humans, $offset, $limit);
    }

    public function bots(): array
    {
        return array_values(array_filter($this->users, static fn (User $u): bool => $u->isBot));
    }
}
