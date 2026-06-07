<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\Round;
use App\Domain\Repository\RoundRepository;
use DateTimeImmutable;

final class InMemoryRoundRepository implements RoundRepository
{
    /** @var array<int, Round> */
    private array $rounds = [];
    /** @var array<int, array<int,array<string,mixed>>> */
    private array $rankings = [];
    private int $seq = 0;

    public function current(): ?Round
    {
        $found = null;
        foreach ($this->rounds as $r) {
            if ($r->isActive()) {
                $found = $r;
            }
        }
        return $found;
    }

    public function start(DateTimeImmutable $now): Round
    {
        $round = new Round(++$this->seq, $now);
        $this->rounds[$round->id] = $round;
        return $round;
    }

    public function end(int $roundId, DateTimeImmutable $now, string $reason): void
    {
        $r = $this->rounds[$roundId] ?? null;
        if ($r !== null) {
            $this->rounds[$roundId] = new Round($r->id, $r->startedAt, $now, $reason);
        }
    }

    public function latestEnded(): ?Round
    {
        $found = null;
        foreach ($this->rounds as $r) {
            if (!$r->isActive()) {
                $found = $r;
            }
        }
        return $found;
    }

    public function saveRankings(int $roundId, array $rows): void
    {
        $this->rankings[$roundId] = $rows;
    }

    public function rankings(int $roundId): array
    {
        return $this->rankings[$roundId] ?? [];
    }
}
