<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exception\InvestException;
use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Invest\InvestInPost;
use App\Domain\Entity\Post;
use App\Domain\Entity\Thread;
use App\Domain\Entity\User;
use App\Domain\Entity\WorldState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ImmediateTransactionManager;
use Tests\Fake\InMemoryHoldingRepository;
use Tests\Fake\InMemoryInvestmentRepository;
use Tests\Fake\InMemoryPostRepository;
use Tests\Fake\InMemoryThreadRepository;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\InMemoryWorldStateRepository;

final class InvestInPostTest extends TestCase
{
    private DateTimeImmutable $now;
    private InMemoryPostRepository $posts;
    private InMemoryThreadRepository $threads;
    private InMemoryUserRepository $users;
    private InMemoryHoldingRepository $holdings;
    private InMemoryInvestmentRepository $investments;
    private InvestInPost $useCase;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-01-01 00:00:00');

        // フェーズは calm 固定（次回抽選は1時間後）→ 倍率 1.0 で決定的
        $world     = new WorldState('calm', 1.0, $this->now->modify('+1 hour'), $this->now);
        $market    = new MarketPhaseService(new InMemoryWorldStateRepository($world));

        $this->posts       = new InMemoryPostRepository();
        $this->threads     = new InMemoryThreadRepository();
        $this->users       = new InMemoryUserRepository();
        $this->holdings    = new InMemoryHoldingRepository();
        $this->investments = new InMemoryInvestmentRepository();

        $this->useCase = new InvestInPost(
            new ImmediateTransactionManager(),
            $market,
            $this->posts,
            $this->threads,
            $this->users,
            $this->holdings,
            $this->investments,
        );
    }

    private function makeThread(int $hp = 300, ?DateTimeImmutable $last = null): void
    {
        $last ??= $this->now;
        $this->threads->insert(new Thread(
            id: 't1', creatorId: null, title: 'T',
            hp: $hp, maxHp: 1000, decayPerMin: 5,
            lastDecayAt: $last, status: 'alive', postCount: 0,
            createdAt: $last, updatedAt: $last,
        ));
    }

    private function makePost(int $hp = 100, int $maxHp = 100, int $invested = 0, int $shares = 0, int $level = 0, ?DateTimeImmutable $last = null): void
    {
        $last ??= $this->now;
        $this->posts->insert(new Post(
            id: 'p1', threadId: 't1', authorHash: 'h', authorId: null, content: 'c',
            hp: $hp, maxHp: $maxHp, decayPerMin: 5,
            totalInvested: $invested, totalShares: $shares, level: $level,
            lastDecayAt: $last, status: 'alive', createdAt: $last, updatedAt: $last,
        ));
    }

    private function makeUser(string $id, int $money): void
    {
        $this->users->insert(new User($id, $id . '@e.com', $id, 'x', $money, $this->now));
    }

    public function testBuysSharesOnBondingCurveAndHealsAndLevelsUp(): void
    {
        $this->makeThread();
        $this->makePost(hp: 50, maxHp: 100, invested: 0); // 株価¥10
        $this->makeUser('bob', 500);

        $r = $this->useCase->execute('bob', 'p1', 100, $this->now);

        self::assertSame(7, $r->shares);       // floor(70 / 10)
        self::assertSame(70, $r->toShares);
        self::assertSame(30, $r->toHp);
        self::assertSame(10.0, $r->price);
        self::assertSame(1, $r->levelAfter);   // total_invested=100 → 注目
        self::assertTrue($r->leveledUp);

        self::assertSame(400, $this->users->findById('bob')->money());
        $post = $this->posts->findById('p1');
        self::assertSame(100, $post->totalInvested());
        self::assertSame(7, $post->totalShares());
        self::assertSame(80, $post->hp());     // 50 + 30
        self::assertSame(300, $post->maxHp()); // レベルアップ

        $holding = $this->holdings->find('bob', 'p1');
        self::assertSame(7, $holding->shares());
        self::assertSame(70, $holding->cost());
        self::assertCount(1, $this->investments->records);
    }

    public function testEarlyInvestorGetsMoreSharesThanLater(): void
    {
        $this->makeThread();
        $this->makePost(hp: 100, maxHp: 100, invested: 0);
        $this->makeUser('alice', 1000);
        $this->makeUser('bob', 1000);

        $early = $this->useCase->execute('alice', 'p1', 100, $this->now); // 株価10 → 7株
        $late  = $this->useCase->execute('bob', 'p1', 100, $this->now);   // 株価11 → 6株

        self::assertGreaterThan($late->shares, $early->shares);
    }

    public function testConservationOfValue(): void
    {
        $this->makeThread();
        $this->makePost();
        $this->makeUser('bob', 1000);

        $r = $this->useCase->execute('bob', 'p1', 333, $this->now);

        self::assertSame(333, $r->toShares + $r->toHp);
    }

    public function testInvestInDeadPostThrows(): void
    {
        $this->makeThread();
        // 10分前確定・HP10・減衰5/分 → 現在HP0 で settle 時に dead
        $this->makePost(hp: 10, last: $this->now->modify('-10 minutes'));
        $this->makeUser('bob', 500);

        $this->expectException(InvestException::class);
        $this->useCase->execute('bob', 'p1', 100, $this->now);
    }

    public function testInvestWhenParentThreadDeadThrows(): void
    {
        // 板が朽ちていれば配下の投稿にも投資不可（カスケード）
        $this->makeThread(hp: 10, last: $this->now->modify('-10 minutes'));
        $this->makePost(hp: 100); // 投稿自体は生存
        $this->makeUser('bob', 500);

        $this->expectException(InvestException::class);
        $this->useCase->execute('bob', 'p1', 100, $this->now);
    }

    public function testInsufficientFundsThrows(): void
    {
        $this->makeThread();
        $this->makePost();
        $this->makeUser('bob', 50);

        $this->expectException(InvestException::class);
        $this->useCase->execute('bob', 'p1', 100, $this->now);
    }

    public function testInvalidAmountThrows(): void
    {
        $this->makeThread();
        $this->makePost();
        $this->makeUser('bob', 500);

        $this->expectException(InvestException::class);
        $this->useCase->execute('bob', 'p1', 0, $this->now);
    }

    public function testTooSmallAmountYieldingZeroSharesIsRejected(): void
    {
        $this->makeThread();
        $this->makePost(invested: 0); // 株価¥10
        $this->makeUser('bob', 500);

        // amount=1 → 株取得額 floor(0.7)=0 → 0株 → 拒否（ロールバックで残高据え置き）
        try {
            $this->useCase->execute('bob', 'p1', 1, $this->now);
            self::fail('InvestException が投げられるはず');
        } catch (InvestException) {
            self::assertSame(500, $this->users->findById('bob')->money());
        }
    }
}
