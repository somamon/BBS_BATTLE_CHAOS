<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\Post;
use App\Domain\Repository\PostRepository;

final class InMemoryPostRepository implements PostRepository
{
    /** @var array<string, Post> */
    private array $posts = [];

    public function findAliveByThread(string $threadId): array
    {
        $alive = array_filter(
            $this->posts,
            static fn (Post $p): bool => $p->threadId === $threadId && $p->isAlive() && !$p->isHidden(),
        );
        return array_values($alive);
    }

    public function findByThread(string $threadId): array
    {
        $byThread = array_filter(
            $this->posts,
            static fn (Post $p): bool => $p->threadId === $threadId && !$p->isHidden(),
        );
        return array_values($byThread);
    }

    public function findLatestByThread(string $threadId): ?Post
    {
        $latest = null;
        foreach ($this->posts as $p) {
            if ($p->threadId === $threadId) {
                $latest = $p; // 挿入順＝古い順なので、最後に見たものが最新
            }
        }
        return $latest;
    }

    public function countAlive(): int
    {
        return count(array_filter($this->posts, static fn (Post $p): bool => $p->isAlive()));
    }

    public function findAlive(int $limit = 100): array
    {
        $alive = array_filter($this->posts, static fn (Post $p): bool => $p->isAlive() && !$p->isHidden());
        return array_slice(array_values($alive), 0, $limit);
    }

    public function recentForAdmin(int $limit = 50): array
    {
        $all = array_values($this->posts);
        usort($all, static fn (Post $a, Post $b): int => $b->createdAt <=> $a->createdAt);
        return array_slice($all, 0, $limit);
    }

    public function findById(string $id): ?Post
    {
        return $this->posts[$id] ?? null;
    }

    public function findByIds(array $ids): array
    {
        $map = [];
        foreach (array_unique($ids) as $id) {
            if (isset($this->posts[$id])) {
                $map[$id] = $this->posts[$id];
            }
        }
        return $map;
    }

    public function findByIdForUpdate(string $id): ?Post
    {
        return $this->findById($id);
    }

    public function insert(Post $post): void
    {
        $this->posts[$post->id] = $post;
    }

    public function save(Post $post): void
    {
        $this->posts[$post->id] = $post;
    }
}
