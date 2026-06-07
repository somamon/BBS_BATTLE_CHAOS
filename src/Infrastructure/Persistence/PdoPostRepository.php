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

    public function findByThread(string $threadId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM posts WHERE thread_id = ? ORDER BY created_at ASC'
        );
        $stmt->execute([$threadId]);

        return array_map(
            fn (array $row): Post => $this->hydrate($row),
            $stmt->fetchAll()
        );
    }

    public function findAlive(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM posts WHERE status = 'alive' ORDER BY created_at DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn (array $row): Post => $this->hydrate($row),
            $stmt->fetchAll()
        );
    }

    public function findById(string $id): ?Post
    {
        $stmt = $this->pdo->prepare('SELECT * FROM posts WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByIds(array $ids): array
    {
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM posts WHERE id IN ({$placeholders})");
        $stmt->execute($ids);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $post = $this->hydrate($row);
            $map[$post->id] = $post;
        }
        return $map;
    }

    public function findByIdForUpdate(string $id): ?Post
    {
        $stmt = $this->pdo->prepare('SELECT * FROM posts WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function insert(Post $post): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO posts
                (id, thread_id, author_hash, author_id, content, hp, max_hp, decay_per_min,
                 total_invested, total_shares, level, last_decay_at, status, created_at, updated_at)
             VALUES
                (:id, :thread_id, :author_hash, :author_id, :content, :hp, :max_hp, :decay_per_min,
                 :total_invested, :total_shares, :level, :last_decay_at, :status, :created_at, :updated_at)'
        );
        $stmt->execute([
            ':id'             => $post->id,
            ':thread_id'      => $post->threadId,
            ':author_hash'    => $post->authorHash,
            ':author_id'      => $post->authorId,
            ':content'        => $post->content,
            ':hp'             => $post->hp(),
            ':max_hp'         => $post->maxHp(),
            ':decay_per_min'  => $post->decayPerMin,
            ':total_invested' => $post->totalInvested(),
            ':total_shares'   => $post->totalShares(),
            ':level'          => $post->level(),
            ':last_decay_at'  => $post->lastDecayAt()->format('Y-m-d H:i:s'),
            ':status'         => $post->status(),
            ':created_at'     => $post->createdAt->format('Y-m-d H:i:s'),
            ':updated_at'     => $post->updatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    public function save(Post $post): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE posts SET
                hp = :hp,
                max_hp = :max_hp,
                total_invested = :total_invested,
                total_shares = :total_shares,
                level = :level,
                last_decay_at = :last_decay_at,
                status = :status,
                updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            ':hp'             => $post->hp(),
            ':max_hp'         => $post->maxHp(),
            ':total_invested' => $post->totalInvested(),
            ':total_shares'   => $post->totalShares(),
            ':level'          => $post->level(),
            ':last_decay_at'  => $post->lastDecayAt()->format('Y-m-d H:i:s'),
            ':status'         => $post->status(),
            ':updated_at'     => $post->updatedAt()->format('Y-m-d H:i:s'),
            ':id'             => $post->id,
        ]);
    }

    private function hydrate(array $row): Post
    {
        return new Post(
            id:            $row['id'],
            threadId:      $row['thread_id'],
            authorHash:    $row['author_hash'],
            authorId:      $row['author_id'],
            content:       $row['content'],
            hp:            (int) $row['hp'],
            maxHp:         (int) $row['max_hp'],
            decayPerMin:   (int) $row['decay_per_min'],
            totalInvested: (int) $row['total_invested'],
            totalShares:   (int) $row['total_shares'],
            level:         (int) $row['level'],
            lastDecayAt:   new DateTimeImmutable($row['last_decay_at']),
            status:        $row['status'],
            createdAt:     new DateTimeImmutable($row['created_at']),
            updatedAt:     new DateTimeImmutable($row['updated_at']),
        );
    }
}
