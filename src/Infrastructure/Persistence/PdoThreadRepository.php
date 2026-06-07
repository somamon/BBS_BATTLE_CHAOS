<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Thread;
use App\Domain\Repository\ThreadRepository;
use DateTimeImmutable;
use PDO;

final class PdoThreadRepository implements ThreadRepository
{
    public function __construct(private PDO $pdo) {}

    public function findAlive(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM threads WHERE status = 'alive' ORDER BY created_at DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn (array $row): Thread => $this->hydrate($row),
            $stmt->fetchAll()
        );
    }

    public function findDead(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM threads WHERE status = 'dead' ORDER BY updated_at DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn (array $row): Thread => $this->hydrate($row),
            $stmt->fetchAll()
        );
    }

    public function findById(string $id): ?Thread
    {
        $stmt = $this->pdo->prepare('SELECT * FROM threads WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByIdForUpdate(string $id): ?Thread
    {
        $stmt = $this->pdo->prepare('SELECT * FROM threads WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function insert(Thread $thread): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO threads
                (id, creator_id, title, hp, max_hp, decay_per_min, mutation_level,
                 total_shares, last_decay_at, status, post_count, created_at, updated_at)
             VALUES
                (:id, :creator_id, :title, :hp, :max_hp, :decay_per_min, :mutation_level,
                 :total_shares, :last_decay_at, :status, :post_count, :created_at, :updated_at)'
        );
        $stmt->execute([
            ':id'             => $thread->id,
            ':creator_id'     => $thread->creatorId,
            ':title'          => $thread->title,
            ':hp'             => $thread->hp(),
            ':max_hp'         => $thread->maxHp(),
            ':decay_per_min'  => $thread->decayPerMin,
            ':mutation_level' => $thread->mutationLevel(),
            ':total_shares'   => $thread->totalShares(),
            ':last_decay_at'  => $thread->lastDecayAt()->format('Y-m-d H:i:s'),
            ':status'         => $thread->status(),
            ':post_count'     => $thread->postCount(),
            ':created_at'     => $thread->createdAt->format('Y-m-d H:i:s'),
            ':updated_at'     => $thread->updatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    public function save(Thread $thread): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE threads SET
                hp = :hp,
                max_hp = :max_hp,
                mutation_level = :mutation_level,
                total_shares = :total_shares,
                last_decay_at = :last_decay_at,
                status = :status,
                post_count = :post_count,
                updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            ':hp'             => $thread->hp(),
            ':max_hp'         => $thread->maxHp(),
            ':mutation_level' => $thread->mutationLevel(),
            ':total_shares'   => $thread->totalShares(),
            ':last_decay_at'  => $thread->lastDecayAt()->format('Y-m-d H:i:s'),
            ':status'         => $thread->status(),
            ':post_count'     => $thread->postCount(),
            ':updated_at'     => $thread->updatedAt()->format('Y-m-d H:i:s'),
            ':id'             => $thread->id,
        ]);
    }

    private function hydrate(array $row): Thread
    {
        return new Thread(
            id:            $row['id'],
            creatorId:     $row['creator_id'],
            title:         $row['title'],
            hp:            (int) $row['hp'],
            maxHp:         (int) $row['max_hp'],
            decayPerMin:   (int) $row['decay_per_min'],
            mutationLevel: (int) $row['mutation_level'],
            totalShares:   (int) $row['total_shares'],
            lastDecayAt:   new DateTimeImmutable($row['last_decay_at']),
            status:        $row['status'],
            postCount:     (int) $row['post_count'],
            createdAt:     new DateTimeImmutable($row['created_at']),
            updatedAt:     new DateTimeImmutable($row['updated_at']),
        );
    }
}
