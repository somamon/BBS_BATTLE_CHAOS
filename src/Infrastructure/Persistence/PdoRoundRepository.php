<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Round;
use App\Domain\Repository\RoundRepository;
use DateTimeImmutable;
use PDO;

final class PdoRoundRepository implements RoundRepository
{
    public function __construct(private PDO $pdo) {}

    public function current(): ?Round
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM rounds WHERE ended_at IS NULL ORDER BY id DESC LIMIT 1'
        );
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function start(DateTimeImmutable $now): Round
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rounds (started_at, ended_at, reason) VALUES (?, NULL, NULL)'
        );
        $stmt->execute([$now->format('Y-m-d H:i:s')]);

        return new Round(
            id: (int) $this->pdo->lastInsertId(),
            startedAt: $now,
        );
    }

    public function end(int $roundId, DateTimeImmutable $now, string $reason): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE rounds SET ended_at = :ended_at, reason = :reason WHERE id = :id'
        );
        $stmt->execute([
            ':ended_at' => $now->format('Y-m-d H:i:s'),
            ':reason'   => $reason,
            ':id'       => $roundId,
        ]);
    }

    public function latestEnded(): ?Round
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM rounds WHERE ended_at IS NOT NULL ORDER BY id DESC LIMIT 1'
        );
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function saveRankings(int $roundId, array $rows): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO round_rankings (round_id, rank_no, user_id, name, cash, share_value, total)
             VALUES (:round_id, :rank_no, :user_id, :name, :cash, :share_value, :total)'
        );
        foreach ($rows as $r) {
            $stmt->execute([
                ':round_id'    => $roundId,
                ':rank_no'     => $r['rank'],
                ':user_id'     => $r['userId'],
                ':name'        => $r['name'],
                ':cash'        => $r['cash'],
                ':share_value' => $r['shareValue'],
                ':total'       => $r['total'],
            ]);
        }
    }

    public function rankings(int $roundId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT rank_no, user_id, name, cash, share_value, total
             FROM round_rankings WHERE round_id = ? ORDER BY rank_no ASC'
        );
        $stmt->execute([$roundId]);

        return array_map(static function (array $row): array {
            return [
                'rank'       => (int) $row['rank_no'],
                'name'       => $row['name'],
                'cash'       => (int) $row['cash'],
                'shareValue' => (int) $row['share_value'],
                'total'      => (int) $row['total'],
            ];
        }, $stmt->fetchAll());
    }

    private function hydrate(array $row): Round
    {
        return new Round(
            id:        (int) $row['id'],
            startedAt: new DateTimeImmutable($row['started_at']),
            endedAt:   $row['ended_at'] !== null ? new DateTimeImmutable($row['ended_at']) : null,
            reason:    $row['reason'],
        );
    }
}
