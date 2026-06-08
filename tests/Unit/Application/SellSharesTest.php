<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exception\InvestException;
use App\Application\Service\DecayRate;
use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Invest\SellShares;
use App\Domain\Entity\Holding;
use App\Domain\Entity\Post;
use App\Domain\Entity\User;
use App\Domain\Entity\WorldState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ImmediateTransactionManager;
use Tests\Fake\InMemoryHoldingRepository;
use Tests\Fake\InMemoryPostRepository;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\InMemoryWorldStateRepository;

final class SellSharesTest extends TestCase
{
    private DateTimeImmutable $now;
    private InMemoryUserRepository $users;
    private InMemoryPostRepository $posts;
    private InMemoryHoldingRepository $holdings;
    private SellShares $useCase;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-01-01 00:00:00');
        $world  = new WorldState('calm', 1.0, $this->now->modify('+1 hour'), $this->now);
        $market = new MarketPhaseService(new InMemoryWorldStateRepository($world));

        $this->users    = new InMemoryUserRepository();
        $this->posts    = new InMemoryPostRepository();
        $this->holdings = new InMemoryHoldingRepository();
        $decay = new DecayRate($market, $this->users);

        $this->useCase = new SellShares(
            new ImmediateTransactionManager(),
            $decay,
            $this->posts,
            $this->users,
            $this->holdings,
        );
    }

    private function seed(int $reserve, int $totalShares, int $myShares, int $money = 0): void
    {
        $this->users->insert(new User('alice', 'a@e.com', 'A', 'x', $money, $this->now));
        $this->posts->insert(new Post(
            id: 'p1', threadId: 't1', authorHash: 'h', authorId: null, content: 'c',
            hp: 100, maxHp: 100, decayPerMin: 5,
            totalInvested: 1000, totalShares: $totalShares, level: 0,
            lastDecayAt: $this->now, status: 'alive', createdAt: $this->now, updatedAt: $this->now,
            reserve: $reserve,
        ));
        $this->holdings->save(new Holding('alice', 'p1', $myShares, 250));
    }

    public function testSellPaysFromReserveAndCredits(): void
    {
        $this->seed(reserve: 1000, totalShares: 10, myShares: 5, money: 0);

        $r = $this->useCase->execute('alice', 'p1', 5, $this->now);

        self::assertSame(500, $r['payout']);                      // 1000 * 5/10 * 鮮度1.0
        self::assertSame(500, $this->users->findById('alice')->money()); // 現金化された
        self::assertSame(500, $this->posts->findById('p1')->reserve());  // リザーブ減少
        self::assertSame(5, $this->posts->findById('p1')->totalShares());
        self::assertSame(0, $this->holdings->find('alice', 'p1')->shares());
    }

    public function testCannotSellMoreThanHeld(): void
    {
        $this->seed(reserve: 1000, totalShares: 10, myShares: 3);
        $this->expectException(InvestException::class);
        $this->useCase->execute('alice', 'p1', 5, $this->now);
    }

    public function testRejectsZeroShares(): void
    {
        $this->seed(reserve: 1000, totalShares: 10, myShares: 5);
        $this->expectException(InvestException::class);
        $this->useCase->execute('alice', 'p1', 0, $this->now);
    }
}
