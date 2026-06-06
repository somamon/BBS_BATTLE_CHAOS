<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\Holding;
use App\Domain\Repository\HoldingRepository;

final class InMemoryHoldingRepository implements HoldingRepository
{
    /** @var array<string, Holding> key = "userId|threadId" */
    private array $holdings = [];

    private function key(string $userId, string $threadId): string
    {
        return $userId . '|' . $threadId;
    }

    public function find(string $userId, string $threadId): ?Holding
    {
        return $this->holdings[$this->key($userId, $threadId)] ?? null;
    }

    public function findByThread(string $threadId): array
    {
        return array_values(array_filter(
            $this->holdings,
            static fn (Holding $h): bool => $h->threadId === $threadId,
        ));
    }

    public function findByUser(string $userId): array
    {
        return array_values(array_filter(
            $this->holdings,
            static fn (Holding $h): bool => $h->userId === $userId,
        ));
    }

    public function save(Holding $holding): void
    {
        $this->holdings[$this->key($holding->userId, $holding->threadId)] = $holding;
    }
}
