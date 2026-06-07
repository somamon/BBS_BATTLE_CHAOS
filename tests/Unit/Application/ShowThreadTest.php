<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Service\DecayRate;
use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Thread\ShowThread;
use App\Domain\Entity\Post;
use App\Domain\Entity\Thread;
use App\Domain\Entity\WorldState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\InMemoryHoldingRepository;
use Tests\Fake\InMemoryPostRepository;
use Tests\Fake\InMemoryThreadRepository;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\InMemoryWorldStateRepository;

final class ShowThreadTest extends TestCase
{
    private DateTimeImmutable $now;
    private InMemoryThreadRepository $threads;
    private InMemoryPostRepository $posts;
    private ShowThread $useCase;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-01-01 00:00:00');
        $world  = new WorldState('calm', 1.0, $this->now->modify('+1 hour'), $this->now);
        $market = new MarketPhaseService(new InMemoryWorldStateRepository($world));
        $decay  = new DecayRate($market, new InMemoryUserRepository());

        $this->threads = new InMemoryThreadRepository();
        $this->posts   = new InMemoryPostRepository();
        $this->useCase = new ShowThread($decay, $this->threads, $this->posts, new InMemoryHoldingRepository());
    }

    private function addPosts(string $threadId, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $this->posts->insert(Post::create($threadId, 'h', null, "reply{$i}", $this->now));
        }
    }

    public function testAliveThreadIsWritable(): void
    {
        $thread = Thread::create(null, 'alive', $this->now, 'ja');
        $this->threads->insert($thread);
        $this->addPosts($thread->id, 2);

        $data = $this->useCase->execute($thread->id, null, $this->now);

        self::assertNotNull($data);
        self::assertTrue($data['thread']['writable']);
        self::assertCount(2, $data['posts']);
    }

    public function testDeadThreadIsReadOnlyButShowsAllPosts(): void
    {
        // 朽ちたスレッド（過去ログ）。
        $thread = new Thread(
            id: 'DEADTHREAD0000000000000001',
            creatorId: null,
            title: 'past log',
            hp: 0,
            maxHp: 1000,
            decayPerMin: 5,
            lastDecayAt: $this->now,
            status: 'dead',
            postCount: 2,
            createdAt: $this->now,
            updatedAt: $this->now,
            lang: 'ja',
        );
        $this->threads->insert($thread);
        $this->addPosts($thread->id, 2);

        $data = $this->useCase->execute($thread->id, null, $this->now);

        self::assertNotNull($data);
        // 書き込み不可だが、レスは閲覧できる。
        self::assertFalse($data['thread']['writable']);
        self::assertCount(2, $data['posts']);
        self::assertSame('reply0', $data['posts'][0]['content']);
    }

    public function testReturnsNullForUnknownThread(): void
    {
        self::assertNull($this->useCase->execute('NOPE', null, $this->now));
    }
}
