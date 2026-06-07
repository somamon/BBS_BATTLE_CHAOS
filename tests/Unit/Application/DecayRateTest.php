<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Service\DecayRate;
use App\Application\Service\MarketPhaseService;
use App\Config\Game;
use App\Domain\Entity\User;
use App\Domain\Entity\WorldState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\InMemoryUserRepository;
use Tests\Fake\InMemoryWorldStateRepository;

final class DecayRateTest extends TestCase
{
    private DateTimeImmutable $now;
    private InMemoryUserRepository $users;

    protected function setUp(): void
    {
        $this->now   = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->users = new InMemoryUserRepository();
    }

    private function decayWithPhase(string $phase, float $phaseMult): DecayRate
    {
        $world  = new WorldState($phase, $phaseMult, $this->now->modify('+1 hour'), $this->now);
        $market = new MarketPhaseService(new InMemoryWorldStateRepository($world));
        return new DecayRate($market, $this->users);
    }

    private function addHumans(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $this->users->insert(new User("h$i", "h$i@e.com", "h$i", 'x', 500, $this->now, $this->now, false));
        }
    }

    private function addBots(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $this->users->insert(new User("b$i", "b$i@e.com", "b$i", 'x', 500, $this->now, $this->now, true));
        }
    }

    public function testSoloDecaysSlowly(): void
    {
        // 人間1人・平常相場 → フェーズ1.0 × 人口係数(少人数)
        $this->addHumans(1);
        $decay = $this->decayWithPhase('calm', 1.0);
        self::assertSame(Game::populationDecayFactor(1), $decay->multiplier($this->now));
    }

    public function testBotsDoNotCountAsPopulation(): void
    {
        // ボットだけ大量にいても「人口0」扱い → 最も遅い
        $this->addBots(30);
        $decay = $this->decayWithPhase('calm', 1.0);
        self::assertSame(Game::DECAY_MIN_FACTOR, $decay->multiplier($this->now));
    }

    public function testFullPopulationReachesPhaseMultiplier(): void
    {
        // 人が十分集まれば人口係数1.0 → フェーズ倍率そのもの（嵐 ×1.8）
        $this->addHumans(Game::DECAY_FULL_AT_HUMANS);
        $decay = $this->decayWithPhase('storm', 1.8);
        self::assertSame(1.8, $decay->multiplier($this->now));
    }
}
