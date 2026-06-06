<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Config\Game;
use PHPUnit\Framework\TestCase;

final class GameTest extends TestCase
{
    public function testPhaseMultiplier(): void
    {
        self::assertSame(0.7, Game::phaseMultiplier('boom'));
        self::assertSame(1.0, Game::phaseMultiplier('calm'));
        self::assertSame(1.8, Game::phaseMultiplier('storm'));
        self::assertSame(2.5, Game::phaseMultiplier('crash'));
        self::assertSame(1.0, Game::phaseMultiplier('unknown'));
    }

    public function testMutationLevelForThresholds(): void
    {
        self::assertSame(0, Game::mutationLevelFor(0));
        self::assertSame(0, Game::mutationLevelFor(499));
        self::assertSame(1, Game::mutationLevelFor(500));
        self::assertSame(1, Game::mutationLevelFor(1999));
        self::assertSame(2, Game::mutationLevelFor(2000));
        self::assertSame(2, Game::mutationLevelFor(7999));
        self::assertSame(3, Game::mutationLevelFor(8000));
        self::assertSame(3, Game::mutationLevelFor(100000));
    }

    public function testMaxHpFor(): void
    {
        self::assertSame(1000, Game::maxHpFor(0));
        self::assertSame(2000, Game::maxHpFor(1));
        self::assertSame(4000, Game::maxHpFor(2));
        self::assertSame(8000, Game::maxHpFor(3));
    }

    public function testDividendBonusFor(): void
    {
        self::assertSame(1.0, Game::dividendBonusFor(0));
        self::assertSame(1.1, Game::dividendBonusFor(1));
        self::assertSame(1.2, Game::dividendBonusFor(2));
        self::assertSame(1.3, Game::dividendBonusFor(3));
    }

    public function testSplitSumsToWhole(): void
    {
        self::assertSame(1.0, Game::SPLIT_HP + Game::SPLIT_DIVIDEND + Game::SPLIT_SINK);
    }
}
