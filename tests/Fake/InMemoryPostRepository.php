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
            static fn (Post $p): bool => $p->threadId === $threadId && $p->isAlive(),
        );
        return array_values($alive);
    }

    public function findAlive(int $limit = 100): array
    {
        $alive = array_filter($this->posts, static fn (Post $p): bool => $p->isAlive());
        return array_slice(array_values($alive), 0, $limit);
    }

    public function findById(string $id): ?Post
    {
        return $this->posts[$id] ?? null;
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
