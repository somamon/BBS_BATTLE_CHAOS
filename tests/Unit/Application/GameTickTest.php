<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Service\DecayRate;
use App\Application\Service\GameTick;
use App\Application\Service\MarketPhaseService;
use App\Application\Service\MarketSimulator;
use App\Application\UseCase\Endgame\EndgameStatus;
use App\Application\UseCase\Endgame\FinalizeAndResetRound;
use App\Application\UseCase\Invest\InvestInPost;
use App\Application\UseCase\Ranking\RankingQuery;
use App\Application\UseCase\Thread\CreateThread;
use App\Application\UseCase\Thread\PostReply;
use App\Domain\Entity\Thread;
use App\Domain\Entity\User;
use App\Domain\Entity\WorldState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeGameStateResetter;
use Tests\Fake\ImmediateTransactionManager;
use Tests\Fake\InMemoryBotSimStateRepository;
use Tests\Fake\InMemoryEmailVerificationRepository;
use Tests\Fake\InMemoryHoldingRepository;
use Tests\Fake\InMemoryInvestmentRepository;
use Tests\Fake\InMemoryPasswordResetRepository;
use Tests\Fake\InMemoryPostRepository;
use Tests\Fake\InMemoryRateLimiter;
use Tests\Fake\InMemoryRoundRepository;
use Tests\Fake\InMemoryThreadRepository;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\InMemoryWorldStateRepository;

final class GameTickTest extends TestCase
{
    private DateTimeImmutable $now;
    private InMemoryUserRepository $users;
    private InMemoryThreadRepository $threads;
    private InMemoryRoundRepository $rounds;
    private FakeGameStateResetter $resetter;

    /** @param DateTimeImmutable $lastTick NPCティックの前回時刻（now-1hなら占有可、now付近なら間隔未満） */
    private function makeTick(DateTimeImmutable $lastTick): GameTick
    {
        $world  = new WorldState('calm', 1.0, $this->now->modify('+1 hour'), $this->now);
        $market = new MarketPhaseService(new InMemoryWorldStateRepository($world));
        $tx     = new ImmediateTransactionManager();

        $this->users   = new InMemoryUserRepository();
        $this->threads = new InMemoryThreadRepository();
        $posts         = new InMemoryPostRepository();
        $holdings      = new InMemoryHoldingRepository();
        $this->rounds  = new InMemoryRoundRepository();
        $this->resetter = new FakeGameStateResetter();

        $decay   = new DecayRate($market, $this->users);
        $invest  = new InvestInPost($tx, $decay, $posts, $this->threads, $this->users, $holdings, new InMemoryInvestmentRepository());
        $sim = new MarketSimulator(
            new InMemoryBotSimStateRepository($lastTick), $this->users, $this->threads, $posts,
            $invest, new PostReply($tx, $decay, $this->threads, $posts), new CreateThread($this->threads),
            new InMemoryRateLimiter(), new InMemoryEmailVerificationRepository(), new InMemoryPasswordResetRepository(),
        );
        $endgame = new EndgameStatus($decay, $this->threads, $this->users);
        $finalize = new FinalizeAndResetRound($tx, $endgame, new RankingQuery($decay, $this->users, $holdings, $posts), $this->rounds, $this->resetter);

        $this->rounds->start($this->now);
        return new GameTick($sim, $finalize);
    }

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-01-01 12:00:00');
    }

    public function testAutoResetsWhenOver(): void
    {
        $tick = $this->makeTick($this->now->modify('-1 hour')); // 占有可
        // ボット無し（NPCは動かない）・人間1人が現金0・生存スレあり → no_money で終局。
        $this->users->insert(new User('h1', 'h@e.com', 'H', 'x', 0, $this->now));
        $this->threads->insert(Thread::create(null, 'alive', $this->now));

        $tick->run($this->now);

        self::assertSame(1, $this->resetter->calls);          // 自動リセットされた
        self::assertSame(2, $this->rounds->current()->id);    // 新ラウンド開始
    }

    public function testNoResetWhenNotOver(): void
    {
        $tick = $this->makeTick($this->now->modify('-1 hour'));
        $this->users->insert(new User('h1', 'h@e.com', 'H', 'x', 1000, $this->now)); // 現金あり
        $this->threads->insert(Thread::create(null, 'alive', $this->now));

        $tick->run($this->now);

        self::assertSame(0, $this->resetter->calls);
        self::assertSame(1, $this->rounds->current()->id);
    }

    public function testSkipsEndgameCheckWhenTickNotClaimed(): void
    {
        $tick = $this->makeTick($this->now); // 直近tick=now → 間隔未満で占有できない
        $this->users->insert(new User('h1', 'h@e.com', 'H', 'x', 0, $this->now)); // 本来は終局
        $this->threads->insert(Thread::create(null, 'alive', $this->now));

        $tick->run($this->now);

        self::assertSame(0, $this->resetter->calls); // 占有できないので終局判定もしない
    }
}
