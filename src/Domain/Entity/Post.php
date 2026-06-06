<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Config\Game;
use App\Domain\Support\Ulid;
use DateTimeImmutable;

/**
 * レス。投資対象外で、時間で朽ちて流れていく（株・変異は持たない）。
 */
final class Post
{
    public function __construct(
        public readonly string $id,
        public readonly string $threadId,
        public readonly string $authorHash,
        public readonly ?string $authorId,
        public readonly string $content,
        private int $hp,
        public readonly int $decayPerMin,
        private DateTimeImmutable $lastDecayAt,
        private string $status,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        string $threadId,
        string $authorHash,
        ?string $authorId,
        string $content,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: Ulid::generate(),
            threadId: $threadId,
            authorHash: $authorHash,
            authorId: $authorId,
            content: $content,
            hp: Game::POST_INIT_HP,
            decayPerMin: Game::POST_DECAY_PER_MIN,
            lastDecayAt: $now,
            status: 'alive',
            createdAt: $now,
        );
    }

    public function currentHp(DateTimeImmutable $now, float $phaseMultiplier): int
    {
        $elapsedMin = ($now->getTimestamp() - $this->lastDecayAt->getTimestamp()) / 60.0;
        $decayed = (int) floor($this->hp - $this->decayPerMin * $phaseMultiplier * $elapsedMin);
        return max(0, $decayed);
    }

    public function isAlive(): bool
    {
        return $this->status === 'alive';
    }

    public function hp(): int { return $this->hp; }
    public function lastDecayAt(): DateTimeImmutable { return $this->lastDecayAt; }
    public function status(): string { return $this->status; }
}
