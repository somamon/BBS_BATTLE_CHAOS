<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Admin\ModeratePost;
use App\Domain\Entity\Post;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeAuditLogger;
use Tests\Fake\InMemoryPostRepository;

final class ModeratePostTest extends TestCase
{
    private InMemoryPostRepository $posts;
    private FakeAuditLogger $audit;
    private ModeratePost $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now     = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->posts   = new InMemoryPostRepository();
        $this->audit   = new FakeAuditLogger();
        $this->useCase = new ModeratePost($this->posts, $this->audit);
    }

    public function testHideRemovesFromPublicAndAudits(): void
    {
        $post = Post::create('t1', 'h', null, '荒らし', $this->now);
        $this->posts->insert($post);

        self::assertCount(1, $this->posts->findAliveByThread('t1')); // 公開で見える

        self::assertTrue($this->useCase->hide('admin1', $post->id, '203.0.113.1'));

        self::assertTrue($this->posts->findById($post->id)->isHidden());
        self::assertSame([], $this->posts->findAliveByThread('t1')); // 公開から消える
        self::assertCount(1, $this->audit->actions('post.hide'));
    }

    public function testUnhideRestores(): void
    {
        $post = Post::create('t1', 'h', null, '誤BAN', $this->now);
        $this->posts->insert($post);
        $this->useCase->hide('admin1', $post->id);

        self::assertTrue($this->useCase->unhide('admin1', $post->id));
        self::assertCount(1, $this->posts->findAliveByThread('t1')); // 復帰
        self::assertCount(1, $this->audit->actions('post.unhide'));
    }

    public function testUnknownReturnsFalse(): void
    {
        self::assertFalse($this->useCase->hide('admin1', 'ghost'));
    }
}
