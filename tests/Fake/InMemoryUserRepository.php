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

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email === $email) {
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

    public function bots(): array
    {
        return array_values(array_filter($this->users, static fn (User $u): bool => $u->isBot));
    }
}
