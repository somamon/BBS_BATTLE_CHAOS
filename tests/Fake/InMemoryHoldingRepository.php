<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\Holding;
use App\Domain\Repository\HoldingRepository;

final class InMemoryHoldingRepository implements HoldingRepository
{
    /** @var array<string, Holding> key = "userId|postId" */
    private array $holdings = [];

    private function key(string $userId, string $postId): string
    {
        return $userId . '|' . $postId;
    }

    public function find(string $userId, string $postId): ?Holding
    {
        return $this->holdings[$this->key($userId, $postId)] ?? null;
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
        $this->holdings[$this->key($holding->userId, $holding->postId)] = $holding;
    }
}
