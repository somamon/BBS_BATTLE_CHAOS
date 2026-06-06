<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * 持ち分。あるユーザーがあるスレッドに対して保有する株数。
 * PK = (userId, threadId)。
 */
final class Holding
{
    public function __construct(
        public readonly string $userId,
        public readonly string $threadId,
        private int $shares,
    ) {}

    public static function empty(string $userId, string $threadId): self
    {
        return new self($userId, $threadId, 0);
    }

    public function addShares(int $shares): void
    {
        $this->shares += $shares;
    }

    public function shares(): int { return $this->shares; }
}
