<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Config\Game;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * HP減衰の実効倍率を一元的に算出する。
 * 実効倍率 = 相場フェーズ倍率（天候）× 人口係数（人が少ないほど朽ちが遅い）。
 *
 * フェーズの遅延遷移は MarketPhaseService に委譲（resolve がフェーズを進めて保存する）。
 */
final class DecayRate
{
    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly UserRepository $users,
    ) {}

    public function multiplier(DateTimeImmutable $now): float
    {
        $phase = $this->market->resolve($now)->multiplier();
        return $phase * Game::populationDecayFactor($this->users->countHumans());
    }
}
