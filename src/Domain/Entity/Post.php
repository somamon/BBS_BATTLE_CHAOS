<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Config\Game;
use App\Domain\Support\Ulid;
use DateTimeImmutable;

/**
 * レス（投稿）。doc21 で投資対象に昇格。
 * 株価（ボンディングカーブ）・レベル・HPロジックをここに集約する（doc21 §2.2-2.5 / §7）。
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
        private int $maxHp,
        public readonly int $decayPerMin,
        private int $totalInvested,
        private int $totalShares,
        private int $level,
        private DateTimeImmutable $lastDecayAt,
        private string $status,
        public readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
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
            maxHp: Game::postMaxHpFor(0),
            decayPerMin: Game::POST_DECAY_PER_MIN,
            totalInvested: 0,
            totalShares: 0,
            level: 0,
            lastDecayAt: $now,
            status: 'alive',
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

    /** HPを回復（max_hpで上限）。吸収しきれなかった超過分を返す（＝ソフトsink）。 */
    public function heal(int $amount, DateTimeImmutable $now): int
    {
        $space   = max(0, $this->maxHp - $this->hp);
        $applied = min($amount, $space);
        $this->hp += $applied;
        $this->updatedAt = $now;
        return $amount - $applied;
    }

    /** スポット株価（鮮度を掛けない素の株価）。total_invested が増えるほど高い。 */
    public function spotPrice(): float
    {
        return Game::sharePrice($this->totalInvested);
    }

    /** 鮮度（0〜1。現在HP/max_hp。朽ちるほど価値が目減り、dead で 0）。 */
    public function freshness(DateTimeImmutable $now, float $phaseMultiplier): float
    {
        if ($this->maxHp <= 0) {
            return 0.0;
        }
        return min(1.0, $this->currentHp($now, $phaseMultiplier) / $this->maxHp);
    }

    /** 保有株の評価額 = 株数 × スポット株価 × 鮮度（マークトゥマーケット）。 */
    public function valuation(int $shares, DateTimeImmutable $now, float $phaseMultiplier): int
    {
        return (int) floor($shares * $this->spotPrice() * $this->freshness($now, $phaseMultiplier));
    }

    /**
     * 投資をこの投稿へ適用する（doc21 §2.3 手順2-8）。settleDecay 済みで alive 前提。
     * 70% で現在株価から株を購入、30% を HP回復に充当し、累計・レベル・max_hp を更新する。
     *
     * @return array{shares:int, price:float, toShares:int, toHp:int}
     */
    public function applyInvestment(int $amount, DateTimeImmutable $now): array
    {
        $price    = $this->spotPrice();                       // 増加前の total_invested で算定
        $toShares = (int) floor($amount * Game::SPLIT_SHARES); // 株取得に回す額（70%）
        $toHp     = $amount - $toShares;                       // 残りをHP回復へ（合計=amount）
        $shares   = (int) floor($toShares / $price);           // 取得株数（0株は呼び出し側で拒否）

        $this->heal($toHp, $now);                              // 超過分は捨てる＝sink
        $this->totalInvested += $amount;
        $this->totalShares   += $shares;

        $newLevel = Game::postLevelFor($this->totalInvested);
        if ($newLevel > $this->level) {
            $this->level = $newLevel;
            $this->maxHp = Game::postMaxHpFor($newLevel);
        }
        $this->updatedAt = $now;

        return ['shares' => $shares, 'price' => $price, 'toShares' => $toShares, 'toHp' => $toHp];
    }

    // --- getters（永続化・表示用） ---
    public function hp(): int { return $this->hp; }
    public function maxHp(): int { return $this->maxHp; }
    public function totalInvested(): int { return $this->totalInvested; }
    public function totalShares(): int { return $this->totalShares; }
    public function level(): int { return $this->level; }
    public function lastDecayAt(): DateTimeImmutable { return $this->lastDecayAt; }
    public function status(): string { return $this->status; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
}
