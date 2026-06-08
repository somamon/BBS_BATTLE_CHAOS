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
        self::assertSame(290, $t->currentHp($this->t0->modify('+120 seconds'), 1.0));
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
        $t->settleDecay($this->t0->modify('+10 minutes'), 1.0);
        self::assertSame(0, $t->hp());
        self::assertSame('dead', $t->status());
        self::assertFalse($t->isAlive());
    }

    public function testSettleDecayKeepsAliveWhenPositive(): void
    {
        $t = $this->thread(hp: 300, decay: 5);
        $t->settleDecay($this->t0->modify('+60 seconds'), 1.0);
        self::assertSame(295, $t->hp());
        self::assertTrue($t->isAlive());
    }

    public function testIncrementPostCount(): void
    {
        $t = $this->thread();
        $t->incrementPostCount($this->t0);
        $t->incrementPostCount($this->t0);
        self::assertSame(2, $t->postCount());
    }

    public function testHealRecoversHp(): void
    {
        $t = $this->thread(hp: 300, maxHp: 1000);
        $t->heal(50);
        self::assertSame(350, $t->hp());
    }

    public function testHealClampsToMaxHp(): void
    {
        $t = $this->thread(hp: 980, maxHp: 1000);
        $t->heal(50);
        self::assertSame(1000, $t->hp());
    }

    public function testHealIgnoresNonPositive(): void
    {
        $t = $this->thread(hp: 300);
        $t->heal(0);
        $t->heal(-100);
        self::assertSame(300, $t->hp());
    }

    public function testHealDoesNothingWhenDead(): void
    {
        $t = $this->thread(hp: 0, status: 'dead');
        $t->heal(100);
        self::assertSame(0, $t->hp());
        self::assertFalse($t->isAlive());
    }
}
