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

    public function testSharePriceBondingCurve(): void
    {
        // BASE=10, SLOPE=0.01
        self::assertSame(10.0, Game::sharePrice(0));
        self::assertSame(11.0, Game::sharePrice(100));
        self::assertSame(20.0, Game::sharePrice(1000));
        self::assertSame(110.0, Game::sharePrice(10000));
    }

    public function testPostLevelForThresholds(): void
    {
        self::assertSame(0, Game::postLevelFor(0));
        self::assertSame(0, Game::postLevelFor(99));
        self::assertSame(1, Game::postLevelFor(100));
        self::assertSame(1, Game::postLevelFor(999));
        self::assertSame(2, Game::postLevelFor(1000));
        self::assertSame(2, Game::postLevelFor(9999));
        self::assertSame(3, Game::postLevelFor(10000));
        self::assertSame(3, Game::postLevelFor(1000000));
    }

    public function testPostMaxHpFor(): void
    {
        self::assertSame(100, Game::postMaxHpFor(0));
        self::assertSame(300, Game::postMaxHpFor(1));
        self::assertSame(800, Game::postMaxHpFor(2));
        self::assertSame(2000, Game::postMaxHpFor(3));
    }

    public function testSplitSumsToWhole(): void
    {
        self::assertSame(1.0, Game::SPLIT_SHARES + Game::SPLIT_HP);
    }

    /** @var string[] テスト内で設定した環境変数（tearDown で確実に戻す） */
    private array $envKeys = [];

    protected function tearDown(): void
    {
        foreach ($this->envKeys as $key) {
            putenv($key); // 値を消す
        }
        $this->envKeys = [];
    }

    private function setEnv(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $this->envKeys[] = $key;
    }

    public function testEnvOverridesScalars(): void
    {
        $this->setEnv('GAME_INITIAL_MONEY', '999');
        $this->setEnv('GAME_MIN_INVEST', '5');
        $this->setEnv('GAME_BOT_MAX_HUMANS', '3');

        self::assertSame(999, Game::initialMoney());
        self::assertSame(5, Game::minInvest());
        self::assertSame(3, Game::botMaxHumans());
    }

    public function testEnvOverridesBondingCurve(): void
    {
        $this->setEnv('GAME_SHARE_PRICE_BASE', '20');
        $this->setEnv('GAME_SHARE_PRICE_SLOPE', '0.05');

        self::assertSame(20.0, Game::sharePrice(0));
        self::assertSame(25.0, Game::sharePrice(100)); // 20 + 0.05*100
    }

    public function testEnvOverridesLevelTiers(): void
    {
        $this->setEnv('GAME_POST_LEVEL_TIERS', '50, 500, 5000');

        self::assertSame(0, Game::postLevelFor(49));
        self::assertSame(1, Game::postLevelFor(50));
        self::assertSame(3, Game::postLevelFor(5000));
    }

    public function testDefaultsWhenEnvUnset(): void
    {
        // 環境変数なしでは const と同値（後方互換）。
        self::assertSame(Game::INITIAL_MONEY, Game::initialMoney());
        self::assertSame(Game::BOT_REFILL_TO, Game::botRefillTo());
        self::assertSame(10.0, Game::sharePrice(0));
    }

    public function testPopulationDecayFactorRampsWithHumans(): void
    {
        // 0人で最も遅く（DECAY_MIN_FACTOR）、DECAY_FULL_AT_HUMANS 人で通常速度(1.0)に到達。
        self::assertSame(Game::DECAY_MIN_FACTOR, Game::populationDecayFactor(0));
        self::assertSame(1.0, Game::populationDecayFactor(Game::DECAY_FULL_AT_HUMANS));
        self::assertSame(1.0, Game::populationDecayFactor(1000)); // 上限は1.0
        // 中間は線形（10人 = 0.3 + 0.7*0.5 = 0.65）
        self::assertEqualsWithDelta(0.65, Game::populationDecayFactor(10), 1e-9);
        // 人が少ないほど遅い（単調増加）
        self::assertLessThan(Game::populationDecayFactor(5), Game::populationDecayFactor(1));
    }
}
