<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exception\BoardException;
use App\Application\Service\DecayRate;
use App\Application\Service\MarketPhaseService;
use App\Application\UseCase\Thread\PostReply;
use App\Domain\Entity\Thread;
use App\Domain\Entity\WorldState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ImmediateTransactionManager;
use Tests\Fake\InMemoryPostRepository;
use Tests\Fake\InMemoryThreadRepository;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\InMemoryWorldStateRepository;

final class PostReplyTest extends TestCase
{
    private DateTimeImmutable $now;
    private InMemoryThreadRepository $threads;
    private InMemoryPostRepository $posts;
    private PostReply $useCase;
    private string $threadId;

    protected function setUp(): void
    {
        // PostReply は内部で実時刻を使うため、スレッドが朽ちないよう「今」を基準にする。
        $this->now = new DateTimeImmutable();
        $world  = new WorldState('calm', 1.0, $this->now->modify('+1 hour'), $this->now);
        $market = new MarketPhaseService(new InMemoryWorldStateRepository($world));
        $decay  = new DecayRate($market, new InMemoryUserRepository());

        $this->threads = new InMemoryThreadRepository();
        $this->posts   = new InMemoryPostRepository();
        $this->useCase = new PostReply(new ImmediateTransactionManager(), $decay, $this->threads, $this->posts);

        $thread = Thread::create(null, 'お題', $this->now, 'ja');
        $this->threads->insert($thread);
        $this->threadId = $thread->id;
    }

    public function testRejectsConsecutiveDuplicateBySameAuthor(): void
    {
        $this->useCase->execute($this->threadId, 'ip-hash-A', null, 'これは伸びる');

        $this->expectException(BoardException::class);
        // 同一IP（authorHash）で直前と同じ本文＝二重カキコ。
        $this->useCase->execute($this->threadId, 'ip-hash-A', null, 'これは伸びる');
    }

    public function testAllowsDifferentContent(): void
    {
        $this->useCase->execute($this->threadId, 'ip-hash-A', null, 'これは伸びる');
        $this->useCase->execute($this->threadId, 'ip-hash-A', null, '草');

        self::assertCount(2, $this->posts->findByThread($this->threadId));
    }

    public function testAllowsSameContentFromDifferentAuthor(): void
    {
        $this->useCase->execute($this->threadId, 'ip-hash-A', null, 'これは伸びる');
        // 別IP（authorHash）なら同じ本文でも許可（別人の同意）。
        $this->useCase->execute($this->threadId, 'ip-hash-B', null, 'これは伸びる');

        self::assertCount(2, $this->posts->findByThread($this->threadId));
    }

    public function testAllowsSameAuthorSameContentIfNotConsecutive(): void
    {
        $this->useCase->execute($this->threadId, 'ip-hash-A', null, 'これは伸びる');
        $this->useCase->execute($this->threadId, 'ip-hash-A', null, '間に挟む');
        // 直前ではないので同じ本文を再度書ける。
        $this->useCase->execute($this->threadId, 'ip-hash-A', null, 'これは伸びる');

        self::assertCount(3, $this->posts->findByThread($this->threadId));
    }
}
