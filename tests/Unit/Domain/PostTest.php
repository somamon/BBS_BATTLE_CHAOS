<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Entity\Post;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PostTest extends TestCase
{
    private function post(int $hp = 100, int $decay = 5, ?DateTimeImmutable $last = null): Post
    {
        $last ??= new DateTimeImmutable('2026-01-01 00:00:00');
        return new Post(
            id: 'p1',
            threadId: 't1',
            authorHash: 'hash',
            authorId: null,
            content: '本文',
            hp: $hp,
            decayPerMin: $decay,
            lastDecayAt: $last,
            status: 'alive',
            createdAt: $last,
        );
    }

    public function testCurrentHpDecays(): void
    {
        $t0 = new DateTimeImmutable('2026-01-01 00:00:00');
        $p  = $this->post(hp: 100, decay: 5, last: $t0);
        self::assertSame(90, $p->currentHp($t0->modify('+120 seconds'), 1.0));
        self::assertSame(0, $p->currentHp($t0->modify('+1 hour'), 1.0));
    }

    public function testIsAlive(): void
    {
        self::assertTrue($this->post()->isAlive());
    }
}
