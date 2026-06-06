<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Entity\Holding;
use PHPUnit\Framework\TestCase;

final class HoldingTest extends TestCase
{
    public function testEmptyStartsAtZeroThenAccumulates(): void
    {
        $h = Holding::empty('u1', 't1');
        self::assertSame(0, $h->shares());

        $h->addShares(50);
        $h->addShares(30);
        self::assertSame(80, $h->shares());
        self::assertSame('u1', $h->userId);
        self::assertSame('t1', $h->threadId);
    }
}
