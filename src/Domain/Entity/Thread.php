<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Config\Game;
use App\Domain\Support\Ulid;
use DateTimeImmutable;

/**
 * スレッド＝板（コンテナ）。doc21 で投資対象から格下げ。
 * 株・変異は持たず、HPが時間で朽ちる「寿命」だけを持つ（doc21 §2.1 / §4）。
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
        private DateTimeImmutable $lastDecayAt,
        private string $status,
        private int $postCount,
        public readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        public readonly string $lang = 'ja',
        private ?DateTimeImmutable $hiddenAt = null,
        private ?string $hiddenBy = null,
    ) {}

    /** 新規スレッドを満タンHPで作る。$lang は立てた時点の表示ロケール（ja/en）。 */
    public static function create(?string $creatorId, string $title, DateTimeImmutable $now, string $lang = 'ja'): self
    {
        return new self(
            id: Ulid::generate(),
            creatorId: $creatorId,
            title: $title,
            hp: Game::threadInitHp(),
            maxHp: Game::threadMaxHp(),
            decayPerMin: Game::threadDecayPerMin(),
            lastDecayAt: $now,
            status: 'alive',
            postCount: 0,
            createdAt: $now,
            updatedAt: $now,
            lang: $lang,
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

    public function incrementPostCount(DateTimeImmutable $now): void
    {
        $this->postCount++;
        $this->updatedAt = $now;
    }

    /** HPを回復する（上限 max_hp、死亡後は回復しない）。レス投稿・投資で板の寿命を延ばす。 */
    public function heal(int $amount): void
    {
        if ($amount <= 0 || !$this->isAlive()) {
            return;
        }
        $this->hp = min($this->maxHp, $this->hp + $amount);
    }

    // --- getters（永続化・表示用） ---
    public function hp(): int { return $this->hp; }
    public function maxHp(): int { return $this->maxHp; }
    public function lastDecayAt(): DateTimeImmutable { return $this->lastDecayAt; }
    public function status(): string { return $this->status; }

    // --- 運営による非表示（モデレーション） ---
    public function isHidden(): bool { return $this->hiddenAt !== null; }
    public function hiddenAt(): ?DateTimeImmutable { return $this->hiddenAt; }
    public function hiddenBy(): ?string { return $this->hiddenBy; }

    public function hide(string $adminId, DateTimeImmutable $now): void
    {
        $this->hiddenAt = $now;
        $this->hiddenBy = $adminId;
    }

    public function unhide(): void
    {
        $this->hiddenAt = null;
        $this->hiddenBy = null;
    }
    public function postCount(): int { return $this->postCount; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
}
