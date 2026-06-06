<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exception\InvestException;
use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Invest\InvestInThread;
use App\Domain\Entity\Holding;
use App\Domain\Entity\Thread;
use App\Domain\Entity\User;
use App\Domain\Entity\WorldState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ImmediateTransactionManager;
use Tests\Fake\InMemoryHoldingRepository;
use Tests\Fake\InMemoryInvestmentRepository;
use Tests\Fake\InMemoryThreadRepository;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\InMemoryWorldStateRepository;

final class InvestInThreadTest extends TestCase
{
    private DateTimeImmutable $now;
    private InMemoryThreadRepository $threads;
    private InMemoryUserRepository $users;
    private InMemoryHoldingRepository $holdings;
    private InMemoryInvestmentRepository $investments;
    private InvestInThread $useCase;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-01-01 00:00:00');

        // フェーズは calm 固定（次回抽選は1時間後＝遷移しない）→ 倍率 1.0 で決定的
        $world      = new WorldState('calm', 1.0, $this->now->modify('+1 hour'), $this->now);
        $worldRepo  = new InMemoryWorldStateRepository($world);
        $market     = new MarketPhaseService($worldRepo);

        $this->threads     = new InMemoryThreadRepository();
        $this->users       = new InMemoryUserRepository();
        $this->holdings    = new InMemoryHoldingRepository();
        $this->investments = new InMemoryInvestmentRepository();

        $this->useCase = new InvestInThread(
            new ImmediateTransactionManager(),
            $market,
            $this->threads,
            $this->users,
            $this->holdings,
            $this->investments,
        );
    }

    private function makeThread(int $hp = 300, int $shares = 0, int $maxHp = 1000, ?DateTimeImmutable $last = null): Thread
    {
        $last ??= $this->now;
        $t = new Thread(
            id: 't1', creatorId: null, title: 'T',
            hp: $hp, maxHp: $maxHp, decayPerMin: 5, mutationLevel: 0,
            totalShares: $shares, lastDecayAt: $last, status: 'alive',
            postCount: 0, createdAt: $last, updatedAt: $last,
        );
        $this->threads->insert($t);
        return $t;
    }

    private function makeUser(string $id, int $money): User
    {
        $u = new User($id, $id . '@e.com', $id, 'x', $money, $this->now);
        $this->users->insert($u);
        return $u;
    }

    private function giveShares(string $userId, int $shares): void
    {
        $h = Holding::empty($userId, 't1');
        $h->addShares($shares);
        $this->holdings->save($h);
    }

    public function testSplitFiftyThirtyTwentyAndDividendToExistingShareholder(): void
    {
        $this->makeThread(hp: 300, shares: 100);
        $this->makeUser('alice', 1000);
        $this->giveShares('alice', 100);   // 既存株主
        $this->makeUser('bob', 500);

        $r = $this->useCase->execute('bob', 't1', 200, $this->now);

        self::assertSame(100, $r->toHp);        // 200 * 0.50
        self::assertSame(60, $r->toDividend);   // 200 * 0.30
        self::assertSame(40, $r->toSink);       // 200 * 0.20
        self::assertSame(1060, $this->users->findById('alice')->money()); // 配当を受領
        self::assertSame(300, $this->users->findById('bob')->money());    // 500 - 200
        self::assertSame(400, $this->threads->findById('t1')->hp());      // 300 + 100（減衰なし）
        self::assertSame(300, $this->threads->findById('t1')->totalShares());
        self::assertSame(200, $this->holdings->find('bob', 't1')->shares());
        self::assertCount(1, $this->investments->records);
    }

    public function testFirstInvestorDividendGoesToSink(): void
    {
        $this->makeThread(hp: 300, shares: 0); // 既存株主なし
        $this->makeUser('bob', 500);

        $r = $this->useCase->execute('bob', 't1', 100, $this->now);

        self::assertSame(50, $r->toHp);
        self::assertSame(0, $r->toDividend);   // 配る相手がいない
        self::assertSame(50, $r->toSink);      // 20% + 配当分が sink へ
        self::assertSame(400, $this->users->findById('bob')->money());
    }

    public function testSelfInvestmentDoesNotPayDividendToSelf(): void
    {
        $this->makeThread(hp: 300, shares: 100);
        $this->makeUser('bob', 500);
        $this->giveShares('bob', 100); // 唯一の株主が投資者本人

        $r = $this->useCase->execute('bob', 't1', 100, $this->now);

        self::assertSame(50, $r->toHp);
        self::assertSame(0, $r->toDividend); // 本人は対象外
        self::assertSame(50, $r->toSink);
        self::assertSame(400, $this->users->findById('bob')->money());
        self::assertSame(200, $this->holdings->find('bob', 't1')->shares());
    }

    public function testMutationTriggeredByInvestment(): void
    {
        $this->makeThread(hp: 300, shares: 400);
        $this->makeUser('bob', 1000);

        $r = $this->useCase->execute('bob', 't1', 100, $this->now); // 400 + 100 = 500 → Lv1

        self::assertTrue($r->mutated);
        self::assertSame(1, $r->mutationLevelAfter);
        self::assertSame(2000, $this->threads->findById('t1')->maxHp());
    }

    public function testInvestInDeadThreadThrows(): void
    {
        // 10分前が最後の確定・HP10・減衰5/分 → 現在HP=0 で settle 時に dead
        $this->makeThread(hp: 10, shares: 0, last: $this->now->modify('-10 minutes'));
        $this->makeUser('bob', 500);

        $this->expectException(InvestException::class);
        $this->useCase->execute('bob', 't1', 100, $this->now);
    }

    public function testInsufficientFundsThrows(): void
    {
        $this->makeThread(hp: 300);
        $this->makeUser('bob', 50);

        $this->expectException(InvestException::class);
        $this->useCase->execute('bob', 't1', 100, $this->now);
    }

    public function testInvalidAmountThrows(): void
    {
        $this->makeThread(hp: 300);
        $this->makeUser('bob', 500);

        $this->expectException(InvestException::class);
        $this->useCase->execute('bob', 't1', 0, $this->now);
    }

    public function testConservationOfValue(): void
    {
        // 投じた額 = HP化 + 配当 + sink が常に成り立つ
        $this->makeThread(hp: 300, shares: 100);
        $this->makeUser('alice', 0);
        $this->giveShares('alice', 100);
        $this->makeUser('bob', 1000);

        $r = $this->useCase->execute('bob', 't1', 333, $this->now);

        self::assertSame(333, $r->toHp + $r->toDividend + $r->toSink);
    }
}
