<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Service\DecayRate;
use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Endgame\EndgameStatus;
use App\Application\UseCase\Endgame\FinalizeAndResetRound;
use App\Application\UseCase\Ranking\RankingQuery;
use App\Config\Game;
use App\Domain\Entity\Thread;
use App\Domain\Entity\User;
use App\Domain\Entity\WorldState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeGameStateResetter;
use Tests\Fake\ImmediateTransactionManager;
use Tests\Fake\InMemoryHoldingRepository;
use Tests\Fake\InMemoryPostRepository;
use Tests\Fake\InMemoryRoundRepository;
use Tests\Fake\InMemoryThreadRepository;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\InMemoryWorldStateRepository;

final class FinalizeAndResetRoundTest extends TestCase
{
    private DateTimeImmutable $now;
    private InMemoryUserRepository $users;
    private InMemoryThreadRepository $threads;
    private InMemoryRoundRepository $rounds;
    private FakeGameStateResetter $resetter;
    private FinalizeAndResetRound $useCase;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-01-01 00:00:00');

        $world  = new WorldState('calm', 1.0, $this->now->modify('+1 hour'), $this->now);
        $market = new MarketPhaseService(new InMemoryWorldStateRepository($world));

        $this->users   = new InMemoryUserRepository();
        $this->threads = new InMemoryThreadRepository();
        $holdings      = new InMemoryHoldingRepository();
        $posts         = new InMemoryPostRepository();
        $this->rounds  = new InMemoryRoundRepository();
        $this->resetter = new FakeGameStateResetter();

        $decay   = new DecayRate($market, $this->users);
        $endgame = new EndgameStatus($decay, $this->threads, $this->users);
        $ranking = new RankingQuery($decay, $this->users, $holdings, $posts);

        $this->useCase = new FinalizeAndResetRound(
            new ImmediateTransactionManager(),
            $endgame,
            $ranking,
            $this->rounds,
            $this->resetter,
        );

        // 進行中ラウンドを1つ用意。
        $this->rounds->start($this->now);
        // 人間プレイヤー1人（ランキングに乗る）。
        $this->users->insert(new User('u1', 'a@e.com', '勝者', 'x', 1000, $this->now));
    }

    public function testForceResetEndsRoundSnapshotsAndStartsNew(): void
    {
        // 生存スレがあり所持金もある＝本来は終局していない。force で強制実行。
        $this->threads->insert(Thread::create(null, 'alive', $this->now));

        $result = $this->useCase->execute(force: true, now: $this->now);

        self::assertTrue($result['reset']);
        self::assertSame(1, $result['endedRound']);
        self::assertSame(2, $result['newRound']);
        self::assertSame('manual', $result['reason']);

        // 初期化が初期所持金で1回呼ばれた。
        self::assertSame(1, $this->resetter->calls);
        self::assertSame(Game::INITIAL_MONEY, $this->resetter->lastHumanMoney);

        // 旧ラウンドは終局・新ラウンドが進行中。
        self::assertNotNull($this->rounds->latestEnded());
        self::assertSame(1, $this->rounds->latestEnded()->id);
        self::assertSame(2, $this->rounds->current()->id);

        // 最終ランキングが旧ラウンドに保存された。
        $snapshot = $this->rounds->rankings(1);
        self::assertCount(1, $snapshot);
        self::assertSame('勝者', $snapshot[0]['name']);
        self::assertSame(1, $snapshot[0]['rank']);
        self::assertSame('u1', $snapshot[0]['userId']);
    }

    public function testNoResetWhenNotOver(): void
    {
        // 生存スレ + 所持金 → 終局していない。force=false なので何もしない。
        $this->threads->insert(Thread::create(null, 'alive', $this->now));

        $result = $this->useCase->execute(force: false, now: $this->now);

        self::assertFalse($result['reset']);
        self::assertSame(0, $this->resetter->calls);
        self::assertNull($this->rounds->latestEnded());
        self::assertSame(1, $this->rounds->current()->id); // 変化なし
    }

    public function testAutoResetWhenAllThreadsDead(): void
    {
        // 生存スレが無い → all_dead で終局。force 不要で自動リセット。
        $result = $this->useCase->execute(force: false, now: $this->now);

        self::assertTrue($result['reset']);
        self::assertSame('all_dead', $result['reason']);
        self::assertSame(1, $this->resetter->calls);
        self::assertSame('all_dead', $this->rounds->latestEnded()->reason);
    }
}
