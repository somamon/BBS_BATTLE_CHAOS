<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Service\DecayRate;
use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Thread\ListThreads;
use App\Domain\Entity\Thread;
use App\Domain\Entity\WorldState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\InMemoryThreadRepository;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\InMemoryWorldStateRepository;

final class ListThreadsTest extends TestCase
{
    private DateTimeImmutable $now;
    private InMemoryThreadRepository $threads;
    private ListThreads $useCase;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-01-01 00:00:00');
        $world  = new WorldState('calm', 1.0, $this->now->modify('+1 hour'), $this->now);
        $market = new MarketPhaseService(new InMemoryWorldStateRepository($world));
        $decay  = new DecayRate($market, new InMemoryUserRepository());

        $this->threads = new InMemoryThreadRepository();
        $this->useCase = new ListThreads($decay, $this->threads);
    }

    private function seed(int $n, string $lang = 'ja'): void
    {
        for ($i = 0; $i < $n; $i++) {
            $this->threads->insert(Thread::create(null, "{$lang}-{$i}", $this->now, $lang));
        }
    }

    public function testPaginatesWithPerPage(): void
    {
        $this->seed(25, 'ja');

        $p1 = $this->useCase->execute('ja', 1, 20, $this->now);
        self::assertCount(20, $p1['items']);
        self::assertSame(1, $p1['page']);
        self::assertSame(25, $p1['total']);
        self::assertSame(2, $p1['totalPages']);

        $p2 = $this->useCase->execute('ja', 2, 20, $this->now);
        self::assertCount(5, $p2['items']);
        self::assertSame(2, $p2['page']);
    }

    public function testPageClampedToRange(): void
    {
        $this->seed(5, 'ja');

        $over = $this->useCase->execute('ja', 99, 20, $this->now);
        self::assertSame(1, $over['totalPages']);
        self::assertSame(1, $over['page']); // 範囲外は最終ページ（=1）に丸める
        self::assertCount(5, $over['items']);
    }

    public function testFiltersByLang(): void
    {
        $this->seed(3, 'ja');
        $this->seed(2, 'en');

        $ja = $this->useCase->execute('ja', 1, 20, $this->now);
        self::assertSame(3, $ja['total']);
        $en = $this->useCase->execute('en', 1, 20, $this->now);
        self::assertSame(2, $en['total']);
    }

    public function testEmptyHasOnePage(): void
    {
        $result = $this->useCase->execute('ja', 1, 20, $this->now);
        self::assertSame([], $result['items']);
        self::assertSame(0, $result['total']);
        self::assertSame(1, $result['totalPages']);
    }
}
