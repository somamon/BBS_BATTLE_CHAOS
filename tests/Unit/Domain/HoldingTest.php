<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Entity\Holding;
use PHPUnit\Framework\TestCase;

final class HoldingTest extends TestCase
{
    public function testEmptyStartsAtZeroThenAccumulates(): void
    {
        $h = Holding::empty('u1', 'p1');
        self::assertSame(0, $h->shares());
        self::assertSame(0, $h->cost());

        $h->addShares(50, 70);
        $h->addShares(30, 40);
        self::assertSame(80, $h->shares());
        self::assertSame(110, $h->cost());   // 取得原価の累計
        self::assertSame('u1', $h->userId);
        self::assertSame('p1', $h->postId);
    }
}
