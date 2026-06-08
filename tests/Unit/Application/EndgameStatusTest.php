<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Service\DecayRate;
use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Endgame\EndgameStatus;
use App\Config\Game;
use App\Domain\Entity\Thread;
use App\Domain\Entity\User;
use App\Domain\Entity\WorldState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\InMemoryRoundRepository;
use Tests\Fake\InMemoryThreadRepository;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\InMemoryWorldStateRepository;

final class EndgameStatusTest extends TestCase
{
    private DateTimeImmutable $now;
    private InMemoryUserRepository $users;
    private InMemoryThreadRepository $threads;
    private InMemoryRoundRepository $rounds;
    private EndgameStatus $useCase;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-01-01 00:00:00');
        $world  = new WorldState('calm', 1.0, $this->now->modify('+1 hour'), $this->now);
        $market = new MarketPhaseService(new InMemoryWorldStateRepository($world));
        $this->users   = new InMemoryUserRepository();
        $this->threads = new InMemoryThreadRepository();
        $this->rounds  = new InMemoryRoundRepository();
        // 進行中シーズンを now に開始（既定1週間。各テストは now 付近で評価するので未満了）。
        $this->rounds->start($this->now);
        $this->useCase = new EndgameStatus(new DecayRate($market, $this->users), $this->threads, $this->users, $this->rounds);
    }

    protected function tearDown(): void
    {
        Game::applyOverrides([]); // env 上書きをクリア
    }

    private function human(string $id, int $money): void
    {
        $this->users->insert(new User($id, $id . '@e.com', $id, 'x', $money, $this->now));
    }

    private function bot(string $id, int $money): void
    {
        $this->users->insert(new User($id, $id . '@b.local', $id, 'x', $money, $this->now, $this->now, true));
    }

    public function testNotOverWithinSeasonWhenThreadsAlive(): void
    {
        $this->threads->insert(Thread::create(null, 'alive', $this->now));
        $this->human('h1', 500);
        self::assertFalse($this->useCase->execute($this->now)['over']);
    }

    public function testTimeUpFiresWhenSeasonDurationElapsed(): void
    {
        $this->threads->insert(Thread::create(null, 'alive', $this->now)); // 生存スレあり・所持金ありでも
        $this->human('h1', 500);
        $after = $this->now->modify('+' . Game::seasonDurationSec() . ' seconds'); // …期間満了なら
        $r = $this->useCase->execute($after);
        self::assertTrue($r['over']);            // 終局し、
        self::assertSame('time_up', $r['reason']); // 理由は time_up。
    }

    public function testNotOverWhenBrokeButSeasonStillRunning(): void
    {
        // 旧 no_money の回帰：現金0でも期間内なら終局しない（"せつない破産"は廃止）。
        $this->threads->insert(Thread::create(null, 'alive', $this->now));
        $this->human('h1', 0);
        $this->bot('b1', 99999);
        self::assertFalse($this->useCase->execute($this->now)['over']);
    }

    public function testTimeUpDisabledWhenDurationZero(): void
    {
        $this->threads->insert(Thread::create(null, 'alive', $this->now));
        $this->human('h1', 500);
        $at = $this->now->modify('+10 seconds'); // スレ生存中の時点で評価

        // 期間=5秒なら time_up で終局するタイミングだが…
        Game::applyOverrides(['GAME_SEASON_DURATION_SEC' => '5']);
        self::assertSame('time_up', $this->useCase->execute($at)['reason']);

        // …0（時間制オフ）なら同じ時点でも終局しない。
        Game::applyOverrides(['GAME_SEASON_DURATION_SEC' => '0']);
        self::assertFalse($this->useCase->execute($at)['over']);
    }

    public function testNoEndgameWhenNoHumans(): void
    {
        $this->bot('b1', 5000); // NPCのみ・人間0人
        // スレも無い（=本来 all_dead）が、無人なので終局しない。
        self::assertFalse($this->useCase->execute($this->now)['over']);
    }

    public function testAllDeadWhenNoAliveThreadsButHumansExist(): void
    {
        $this->human('h1', 500); // 人間あり・生存スレ無し・期間内
        $r = $this->useCase->execute($this->now);
        self::assertTrue($r['over']);
        self::assertSame('all_dead', $r['reason']);
    }
}
