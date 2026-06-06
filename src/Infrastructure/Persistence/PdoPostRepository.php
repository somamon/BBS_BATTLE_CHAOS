<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Post;
use App\Domain\Repository\PostRepository;
use DateTimeImmutable;
use PDO;

final class PdoPostRepository implements PostRepository
{
    public function __construct(private PDO $pdo) {}

    public function findAliveByThread(string $threadId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM posts WHERE thread_id = ? AND status = 'alive' ORDER BY created_at ASC"
        );
        $stmt->execute([$threadId]);

        return array_map(
            fn (array $row): Post => $this->hydrate($row),
            $stmt->fetchAll()
        );
    }

    public function insert(Post $post): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO posts
                (id, thread_id, author_hash, author_id, content, hp, decay_per_min,
                 last_decay_at, status, created_at)
             VALUES
                (:id, :thread_id, :author_hash, :author_id, :content, :hp, :decay_per_min,
                 :last_decay_at, :status, :created_at)'
        );
        $stmt->execute([
            ':id'            => $post->id,
            ':thread_id'     => $post->threadId,
            ':author_hash'   => $post->authorHash,
            ':author_id'     => $post->authorId,
            ':content'       => $post->content,
            ':hp'            => $post->hp(),
            ':decay_per_min' => $post->decayPerMin,
            ':last_decay_at' => $post->lastDecayAt()->format('Y-m-d H:i:s'),
            ':status'        => $post->status(),
            ':created_at'    => $post->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    private function hydrate(array $row): Post
    {
        return new Post(
            id:          $row['id'],
            threadId:    $row['thread_id'],
            authorHash:  $row['author_hash'],
            authorId:    $row['author_id'],
            content:     $row['content'],
            hp:          (int) $row['hp'],
            decayPerMin: (int) $row['decay_per_min'],
            lastDecayAt: new DateTimeImmutable($row['last_decay_at']),
            status:      $row['status'],
            createdAt:   new DateTimeImmutable($row['created_at']),
        );
    }
}
