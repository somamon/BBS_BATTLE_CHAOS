<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\Thread;
use App\Domain\Repository\ThreadRepository;

final class InMemoryThreadRepository implements ThreadRepository
{
    /** @var array<string, Thread> */
    private array $threads = [];

    public function findAlive(int $limit = 50): array
    {
        $alive = array_filter($this->threads, static fn (Thread $t): bool => $t->isAlive());
        return array_slice(array_values($alive), 0, $limit);
    }

    public function findById(string $id): ?Thread
    {
        return $this->threads[$id] ?? null;
    }

    public function findByIdForUpdate(string $id): ?Thread
    {
        return $this->findById($id);
    }

    public function insert(Thread $thread): void
    {
        $this->threads[$thread->id] = $thread;
    }

    public function save(Thread $thread): void
    {
        $this->threads[$thread->id] = $thread;
    }
}
