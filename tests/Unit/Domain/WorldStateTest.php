<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Entity\WorldState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class WorldStateTest extends TestCase
{
    public function testShouldShiftAtOrAfterNextShiftAt(): void
    {
        $t0    = new DateTimeImmutable('2026-01-01 00:00:00');
        $state = new WorldState('calm', 1.0, $t0->modify('+60 seconds'), $t0);

        self::assertFalse($state->shouldShift($t0));
        self::assertTrue($state->shouldShift($t0->modify('+60 seconds')));
        self::assertTrue($state->shouldShift($t0->modify('+61 seconds')));
    }

    public function testShiftToUpdatesPhaseAndMultiplier(): void
    {
        $t0    = new DateTimeImmutable('2026-01-01 00:00:00');
        $state = new WorldState('calm', 1.0, $t0, $t0);

        $next = $t0->modify('+300 seconds');
        $state->shiftTo('storm', $next, $t0);

        self::assertSame('storm', $state->phase());
        self::assertSame(1.8, $state->multiplier());
        self::assertEquals($next, $state->nextShiftAt());
    }
}
