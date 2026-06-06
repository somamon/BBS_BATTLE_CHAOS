<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Config\Game;
use App\Domain\Support\Ulid;
use DateTimeImmutable;

/**
 * スレッド。投資対象であり、HPが時間で朽ち、累計投資で変異する。
 * 遅延減衰・回復・変異のドメインロジックをここに集約する（docs/design/02, 04）。
 */
final class Thread
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $creatorId,
        public readonly string $title,
        private int $hp,
        private int $maxHp,
        public readonly int $decayPerMin,
        private int $mutationLevel,
        private int $totalShares,
        private DateTimeImmutable $lastDecayAt,
        private string $status,
        private int $postCount,
        public readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    /** 新規スレッドを満タンHPで作る。 */
    public static function create(?string $creatorId, string $title, DateTimeImmutable $now): self
    {
        return new self(
            id: Ulid::generate(),
            creatorId: $creatorId,
            title: $title,
            hp: Game::THREAD_INIT_HP,
            maxHp: Game::THREAD_MAX_HP,
            decayPerMin: Game::THREAD_DECAY_PER_MIN,
            mutationLevel: 0,
            totalShares: 0,
            lastDecayAt: $now,
            status: 'alive',
            postCount: 0,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /** 現在HP（確定HPから経過時間×減衰率×フェーズ倍率を引いた値、下限0）。純粋計算。 */
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

    /** 現在HPを確定値へ書き戻し、0なら dead に遷移させる（復活不可）。 */
    public function settleDecay(DateTimeImmutable $now, float $phaseMultiplier): void
    {
        $this->hp = $this->currentHp($now, $phaseMultiplier);
        $this->lastDecayAt = $now;
        $this->updatedAt = $now;
        if ($this->hp <= 0) {
            $this->status = 'dead';
        }
    }

    /** HPを回復（max_hpで上限）。吸収しきれなかった超過分を返す。 */
    public function heal(int $amount, DateTimeImmutable $now): int
    {
        $space   = max(0, $this->maxHp - $this->hp);
        $applied = min($amount, $space);
        $this->hp += $applied;
        $this->updatedAt = $now;
        return $amount - $applied;
    }

    /** 株を発行し、閾値到達で変異（max_hp引き上げ）。 */
    public function issueShares(int $shares, DateTimeImmutable $now): void
    {
        $this->totalShares += $shares;
        $newLevel = Game::mutationLevelFor($this->totalShares);
        if ($newLevel > $this->mutationLevel) {
            $this->mutationLevel = $newLevel;
            $this->maxHp = Game::maxHpFor($newLevel);
        }
        $this->updatedAt = $now;
    }

    /** いまの変異レベルに応じた配当ボーナス倍率。 */
    public function dividendBonus(): float
    {
        return Game::dividendBonusFor($this->mutationLevel);
    }

    public function incrementPostCount(DateTimeImmutable $now): void
    {
        $this->postCount++;
        $this->updatedAt = $now;
    }

    // --- getters（永続化・表示用） ---
    public function hp(): int { return $this->hp; }
    public function maxHp(): int { return $this->maxHp; }
    public function mutationLevel(): int { return $this->mutationLevel; }
    public function totalShares(): int { return $this->totalShares; }
    public function lastDecayAt(): DateTimeImmutable { return $this->lastDecayAt; }
    public function status(): string { return $this->status; }
    public function postCount(): int { return $this->postCount; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
}
