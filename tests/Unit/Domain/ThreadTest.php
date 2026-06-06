<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Entity\Thread;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ThreadTest extends TestCase
{
    private DateTimeImmutable $t0;

    protected function setUp(): void
    {
        $this->t0 = new DateTimeImmutable('2026-01-01 00:00:00');
    }

    private function thread(
        int $hp = 300,
        int $maxHp = 1000,
        int $decay = 5,
        int $mutation = 0,
        int $shares = 0,
        string $status = 'alive',
        ?DateTimeImmutable $last = null,
    ): Thread {
        $last ??= $this->t0;
        return new Thread(
            id: 't1',
            creatorId: null,
            title: 'テスト',
            hp: $hp,
            maxHp: $maxHp,
            decayPerMin: $decay,
            mutationLevel: $mutation,
            totalShares: $shares,
            lastDecayAt: $last,
            status: $status,
            postCount: 0,
            createdAt: $last,
            updatedAt: $last,
        );
    }

    public function testCurrentHpDecaysWithTimeAndPhase(): void
    {
        $t = $this->thread(hp: 300, decay: 5);
        // 2分経過・平常(×1.0) → 300 - 5*2 = 290
        self::assertSame(290, $t->currentHp($this->t0->modify('+120 seconds'), 1.0));
        // 同じ経過・嵐(×2.0) → 300 - 5*2*2 = 280
        self::assertSame(280, $t->currentHp($this->t0->modify('+120 seconds'), 2.0));
    }

    public function testCurrentHpFloorsAtZero(): void
    {
        $t = $this->thread(hp: 300, decay: 5);
        self::assertSame(0, $t->currentHp($this->t0->modify('+1 day'), 1.0));
    }

    public function testSettleDecayMarksDeadWhenDepleted(): void
    {
        $t = $this->thread(hp: 10, decay: 5);
        $t->settleDecay($this->t0->modify('+10 minutes'), 1.0); // 10 - 50 → 0
        self::assertSame(0, $t->hp());
        self::assertSame('dead', $t->status());
        self::assertFalse($t->isAlive());
    }

    public function testSettleDecayKeepsAliveWhenPositive(): void
    {
        $t = $this->thread(hp: 300, decay: 5);
        $t->settleDecay($this->t0->modify('+60 seconds'), 1.0); // 300 - 5 = 295
        self::assertSame(295, $t->hp());
        self::assertTrue($t->isAlive());
    }

    public function testHealCapsAtMaxHpAndReturnsOverflow(): void
    {
        $t = $this->thread(hp: 900, maxHp: 1000);
        $overflow = $t->heal(250, $this->t0);
        self::assertSame(150, $overflow);
        self::assertSame(1000, $t->hp());
    }

    public function testHealWithinCapHasNoOverflow(): void
    {
        $t = $this->thread(hp: 300, maxHp: 1000);
        self::assertSame(0, $t->heal(50, $this->t0));
        self::assertSame(350, $t->hp());
    }

    public function testIssueSharesTriggersMutation(): void
    {
        $t = $this->thread(maxHp: 1000, shares: 400);
        $t->issueShares(100, $this->t0); // 500 → Lv1
        self::assertSame(1, $t->mutationLevel());
        self::assertSame(2000, $t->maxHp());
        self::assertSame(500, $t->totalShares());
        self::assertSame(1.1, $t->dividendBonus());
    }

    public function testIssueSharesReachesLevelTwo(): void
    {
        $t = $this->thread(maxHp: 1000, shares: 1900);
        $t->issueShares(100, $this->t0); // 2000 → Lv2
        self::assertSame(2, $t->mutationLevel());
        self::assertSame(4000, $t->maxHp());
    }

    public function testIssueSharesBelowThresholdDoesNotMutate(): void
    {
        $t = $this->thread(shares: 0);
        $t->issueShares(100, $this->t0);
        self::assertSame(0, $t->mutationLevel());
        self::assertSame(100, $t->totalShares());
    }

    public function testIncrementPostCount(): void
    {
        $t = $this->thread();
        $t->incrementPostCount($this->t0);
        $t->incrementPostCount($this->t0);
        self::assertSame(2, $t->postCount());
    }
}
