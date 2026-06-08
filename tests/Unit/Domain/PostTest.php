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
        int $reserve = 0,
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
            reserve: $reserve,
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

    public function testValuationIsReserveProRataByFreshness(): void
    {
        // 評価額＝いま売ったら得る額＝リザーブ按分×鮮度。
        // リザーブ1000・総株10・鮮度1.0 → 7株は 1000*7/10 = 700。
        $p = $this->post(hp: 100, maxHp: 100, shares: 10, reserve: 1000);
        self::assertSame(700, $p->valuation(7, $this->t0, 1.0));

        // 鮮度0.5 → 350。
        $half = $this->post(hp: 50, maxHp: 100, shares: 10, reserve: 1000);
        self::assertSame(350, $half->valuation(7, $this->t0, 1.0));

        // リザーブ0なら0。
        self::assertSame(0, $this->post(shares: 10, reserve: 0)->valuation(7, $this->t0, 1.0));
    }

    public function testSellPaysFromReserveAndReducesState(): void
    {
        $p = $this->post(hp: 100, maxHp: 100, shares: 10, reserve: 1000);
        $payout = $p->sell(5, $this->t0, 1.0); // 1000*5/10*1.0 = 500
        self::assertSame(500, $payout);
        self::assertSame(500, $p->reserve());
        self::assertSame(5, $p->totalShares());
    }

    public function testSellDeadPostPaysZero(): void
    {
        // HP枯渇（鮮度0）→ 払い戻し0。リザーブは減らない。
        $p = $this->post(hp: 0, maxHp: 100, shares: 10, reserve: 1000);
        self::assertSame(0, $p->sell(5, $this->t0, 1.0));
        self::assertSame(1000, $p->reserve());
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
        self::assertSame(70, $p->reserve());   // 株購入分(70%)がリザーブへ積み立てられる
    }

    public function testEarlyBuyerGetsMoreSharesThanLater(): void
    {
        $early = $this->post(invested: 0)->applyInvestment(100, $this->t0);     // 株価10 → 7株
        $late  = $this->post(invested: 100)->applyInvestment(100, $this->t0);   // 株価11 → 6株
        self::assertGreaterThan($late['shares'], $early['shares']);
    }

    public function testLaterInvestmentLiftsEarlyHolderValue(): void
    {
        // 後から（高い株価で）投資が入ると、早期保有者の評価額が上がる＝目利きが報われる。
        $p = $this->post(hp: 2000, maxHp: 2000, invested: 10000, shares: 0, level: 3);
        $p->applyInvestment(11000, $this->t0);   // 株価110で購入
        $earlyShares = $p->totalShares();
        $before = $p->valuation($earlyShares, $this->t0, 1.0);

        $p->applyInvestment(50000, $this->t0);   // さらに高い株価で後続投資
        $after = $p->valuation($earlyShares, $this->t0, 1.0);

        self::assertGreaterThan($before, $after);
    }
}
