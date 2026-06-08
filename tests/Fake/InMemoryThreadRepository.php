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
        $alive = array_filter($this->threads, static fn (Thread $t): bool => $t->isAlive() && !$t->isHidden());
        return array_slice(array_values($alive), 0, $limit);
    }

    public function findDead(int $limit = 100): array
    {
        $dead = array_filter($this->threads, static fn (Thread $t): bool => !$t->isAlive() && !$t->isHidden());
        return array_slice(array_values($dead), 0, $limit);
    }

    public function findAliveByLang(string $lang, int $limit = 50, int $offset = 0): array
    {
        $alive = array_filter(
            $this->threads,
            static fn (Thread $t): bool => $t->isAlive() && !$t->isHidden() && $t->lang === $lang,
        );
        return array_slice(array_values($alive), $offset, $limit);
    }

    public function countAliveByLang(string $lang): int
    {
        return count(array_filter(
            $this->threads,
            static fn (Thread $t): bool => $t->isAlive() && !$t->isHidden() && $t->lang === $lang,
        ));
    }

    public function countAlive(): int
    {
        return count(array_filter($this->threads, static fn (Thread $t): bool => $t->isAlive() && !$t->isHidden()));
    }

    public function findDeadByLang(string $lang, int $limit = 100): array
    {
        $dead = array_filter(
            $this->threads,
            static fn (Thread $t): bool => !$t->isAlive() && !$t->isHidden() && $t->lang === $lang,
        );
        return array_slice(array_values($dead), 0, $limit);
    }

    public function recentForAdmin(int $limit = 50, int $offset = 0): array
    {
        $all = array_values($this->threads);
        usort($all, static fn (Thread $a, Thread $b): int => $b->createdAt <=> $a->createdAt);
        return array_slice($all, $offset, $limit);
    }

    public function countForAdmin(): int
    {
        return count($this->threads);
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
