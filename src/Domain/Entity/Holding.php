<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * 持ち分。あるユーザーがある投稿(post)に対して保有する株数と取得原価。
 * PK = (userId, postId)。取得原価は含み損益（評価額−原価）算出に使う。
 */
final class Holding
{
    public function __construct(
        public readonly string $userId,
        public readonly string $postId,
        private int $shares,
        private int $cost = 0,
    ) {}

    public static function empty(string $userId, string $postId): self
    {
        return new self($userId, $postId, 0, 0);
    }

    /** 株を加算し、株取得に支払った額（70%分）を取得原価へ積む。 */
    public function addShares(int $shares, int $cost = 0): void
    {
        $this->shares += $shares;
        $this->cost   += $cost;
    }

    public function shares(): int { return $this->shares; }
    public function cost(): int { return $this->cost; }
}
