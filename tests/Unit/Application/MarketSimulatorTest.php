<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Service\DecayRate;
use App\Application\Service\MarketPhaseService;
use App\Application\Service\MarketSimulator;
use App\Application\UseCase\Invest\InvestInPost;
use App\Application\UseCase\Thread\CreateThread;
use App\Application\UseCase\Thread\PostReply;
use App\Domain\Entity\Post;
use App\Domain\Entity\Thread;
use App\Domain\Entity\User;
use App\Domain\Entity\WorldState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ImmediateTransactionManager;
use Tests\Fake\InMemoryBotSimStateRepository;
use Tests\Fake\InMemoryEmailVerificationRepository;
use Tests\Fake\InMemoryHoldingRepository;
use Tests\Fake\InMemoryInvestmentRepository;
use Tests\Fake\InMemoryPasswordResetRepository;
use Tests\Fake\InMemoryPostRepository;
use Tests\Fake\InMemoryRateLimiter;
use Tests\Fake\InMemoryThreadRepository;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\InMemoryWorldStateRepository;

final class MarketSimulatorTest extends TestCase
{
    private DateTimeImmutable $now;
    private InMemoryUserRepository $users;
    private InMemoryThreadRepository $threads;
    private InMemoryPostRepository $posts;
    private InMemoryInvestmentRepository $investments;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-01-01 12:00:00');
    }

    private function makeSimulator(DateTimeImmutable $lastTick): MarketSimulator
    {
        $world  = new WorldState('calm', 1.0, $this->now->modify('+1 hour'), $this->now);
        $market = new MarketPhaseService(new InMemoryWorldStateRepository($world));
        $tx     = new ImmediateTransactionManager();

        $this->users       = new InMemoryUserRepository();
        $this->threads     = new InMemoryThreadRepository();
        $this->posts       = new InMemoryPostRepository();
        $holdings          = new InMemoryHoldingRepository();
        $this->investments = new InMemoryInvestmentRepository();
        $simState          = new InMemoryBotSimStateRepository($lastTick);

        $decay       = new DecayRate($market, $this->users);
        $invest      = new InvestInPost($tx, $decay, $this->posts, $this->threads, $this->users, $holdings, $this->investments);
        $postReply   = new PostReply($tx, $decay, $this->threads, $this->posts);
        $createThread = new CreateThread($this->threads);

        return new MarketSimulator(
            $simState, $this->users, $this->threads, $this->posts,
            $invest, $postReply, $createThread,
            new InMemoryRateLimiter(), new InMemoryEmailVerificationRepository(),
            new InMemoryPasswordResetRepository(),
        );
    }

    private function addBot(string $id, int $money = 5000): void
    {
        $this->users->insert(new User($id, $id . '@bots.local', $id, 'x', $money, $this->now, $this->now, true));
    }

    private function addHumans(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $this->users->insert(new User("h$i", "h$i@e.com", "h$i", 'x', 500, $this->now, $this->now, false));
        }
    }

    public function testDormantWhenHumansExceedLimit(): void
    {
        $sim = $this->makeSimulator($this->now->modify('-1 hour')); // 十分な経過
        $this->addHumans(51); // 上限50超
        $this->addBot('b1');

        $sim->tick($this->now);

        self::assertCount(0, $this->threads->findAlive());
        self::assertCount(0, $this->investments->records);
    }

    public function testNoActionWhenNoTimeElapsed(): void
    {
        $sim = $this->makeSimulator($this->now); // lastTick == now → 経過0
        $this->addHumans(1);
        $this->addBot('b1');

        $sim->tick($this->now);

        self::assertCount(0, $this->threads->findAlive());
    }

    public function testBootstrapsThreadWhenWorldIsEmpty(): void
    {
        $sim = $this->makeSimulator($this->now->modify('-5 minutes')); // 経過300s → 数アクション
        $this->addHumans(1);
        $this->addBot('b1');

        $sim->tick($this->now);

        // 板が無い状態の最初のアクションは必ずスレ立て。
        self::assertGreaterThanOrEqual(1, count($this->threads->findAlive()));
    }

    public function testBotsInvestAndRaisePriceOverTime(): void
    {
        $sim = $this->makeSimulator($this->now->modify('-1 hour'));
        $this->addHumans(1);
        $this->addBot('b1');
        $this->addBot('b2');

        // 投資対象を用意（株価¥10スタート）。
        $thread = Thread::create(null, 'seed', $this->now);
        $this->threads->insert($thread);
        for ($i = 0; $i < 5; $i++) {
            $this->posts->insert(Post::create($thread->id, 'h', null, "post$i", $this->now));
        }

        // 複数tickでアクションを蓄積（各tickは最大BOT_MAX_BURST件）。
        $t = $this->now;
        for ($k = 0; $k < 12; $k++) {
            $t = $t->modify('+5 minutes');
            $sim->tick($t);
        }

        self::assertNotEmpty($this->investments->records, 'ボットが一度も投資しなかった');

        // 投資を受けた投稿は累計投資>0 → スポット株価が基準(¥10)より上がっている。
        $raised = false;
        foreach ($this->posts->findAlive() as $p) {
            if ($p->totalInvested() > 0 && $p->spotPrice() > 10.0) {
                $raised = true;
                break;
            }
        }
        self::assertTrue($raised, '投資で株価が上がった投稿が無い');
    }

    public function testDoesNotRefillBotWhenOverMoneyCeiling(): void
    {
        putenv('GAME_MONEY_CEILING=0'); // 天井0＝常に上限超過扱い→補充しない
        try {
            $sim = $this->makeSimulator($this->now->modify('-1 hour'));
            $this->addHumans(1);
            $this->addBot('b1', money: 0); // 資金切れボット
            $thread = Thread::create(null, 'seed', $this->now);
            $this->threads->insert($thread);
            $this->posts->insert(Post::create($thread->id, 'h', null, 'p', $this->now));

            $t = $this->now;
            for ($k = 0; $k < 6; $k++) {
                $t = $t->modify('+5 minutes');
                $sim->tick($t);
            }

            self::assertSame(0, $this->users->findById('b1')->money(), '天井超過なのに補充された');
            self::assertSame([], $this->investments->records, '資金0なのに投資が成立した');
        } finally {
            putenv('GAME_MONEY_CEILING');
        }
    }
}
