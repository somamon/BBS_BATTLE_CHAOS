<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Entity\Post;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PostTest extends TestCase
{
    private DateTimeImmutable $t0;

    protected function setUp(): void
    {
        $this->t0 = new DateTimeImmutable('2026-01-01 00:00:00');
    }

    private function post(
        int $hp = 100,
        int $maxHp = 100,
        int $decay = 5,
        int $invested = 0,
        int $shares = 0,
        int $level = 0,
        string $status = 'alive',
        ?DateTimeImmutable $last = null,
    ): Post {
        $last ??= $this->t0;
        return new Post(
            id: 'p1',
            threadId: 't1',
            authorHash: 'hash',
            authorId: null,
            content: '本文',
            hp: $hp,
            maxHp: $maxHp,
            decayPerMin: $decay,
            totalInvested: $invested,
            totalShares: $shares,
            level: $level,
            lastDecayAt: $last,
            status: $status,
            createdAt: $last,
            updatedAt: $last,
        );
    }

    public function testCurrentHpDecays(): void
    {
        $p = $this->post(hp: 100, decay: 5);
        self::assertSame(90, $p->currentHp($this->t0->modify('+120 seconds'), 1.0));
        self::assertSame(0, $p->currentHp($this->t0->modify('+1 hour'), 1.0));
    }

    public function testSettleDecayMarksDeadWhenDepleted(): void
    {
        $p = $this->post(hp: 10, decay: 5);
        $p->settleDecay($this->t0->modify('+10 minutes'), 1.0);
        self::assertSame(0, $p->hp());
        self::assertFalse($p->isAlive());
    }

    public function testSpotPriceRisesWithTotalInvested(): void
    {
        // BASE=10, SLOPE=0.01
        self::assertSame(10.0, $this->post(invested: 0)->spotPrice());
        self::assertSame(11.0, $this->post(invested: 100)->spotPrice());
        self::assertSame(110.0, $this->post(invested: 10000)->spotPrice());
    }

    public function testFreshnessIsCurrentHpOverMaxHp(): void
    {
        $p = $this->post(hp: 150, maxHp: 300);
        self::assertSame(0.5, $p->freshness($this->t0, 1.0));
    }

    public function testValuationMarkToMarket(): void
    {
        // doc21 §2.4 の例: 7株・total_invested=10000（株価¥110）・鮮度1.0 → 770
        $p = $this->post(hp: 2000, maxHp: 2000, invested: 10000, shares: 7, level: 3);
        self::assertSame(770, $p->valuation(7, $this->t0, 1.0));
    }

    public function testApplyInvestmentBuysSharesHealsAndLevelsUp(): void
    {
        // hp=50/maxHp=100, total_invested=0（株価¥10）
        $p = $this->post(hp: 50, maxHp: 100, invested: 0);
        $r = $p->applyInvestment(100, $this->t0);

        self::assertSame(70, $r['toShares']);  // floor(100 * 0.70)
        self::assertSame(30, $r['toHp']);      // 100 - 70
        self::assertSame(7, $r['shares']);     // floor(70 / 10)
        self::assertSame(10.0, $r['price']);   // 増加前の株価
        self::assertSame(80, $p->hp());        // 50 + 30（max_hp内）
        self::assertSame(100, $p->totalInvested());
        self::assertSame(7, $p->totalShares());
        self::assertSame(1, $p->level());      // total_invested=100 → 注目
        self::assertSame(300, $p->maxHp());    // レベルアップで上昇
    }

    public function testEarlyBuyerGetsMoreSharesThanLater(): void
    {
        $early = $this->post(invested: 0)->applyInvestment(100, $this->t0);     // 株価10 → 7株
        $late  = $this->post(invested: 100)->applyInvestment(100, $this->t0);   // 株価11 → 6株
        self::assertGreaterThan($late['shares'], $early['shares']);
    }
}
