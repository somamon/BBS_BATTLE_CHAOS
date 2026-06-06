<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Config\Game;
use App\Domain\Entity\WorldState;
use App\Domain\Repository\WorldStateRepository;
use DateInterval;
use DateTimeImmutable;

/**
 * 世界フェーズ（相場天候）の遅延遷移を司る（docs/design/04 §3）。
 * 任意アクセス時に呼び、抽選時刻を過ぎていればフェーズを引き直して保存する。cron 不要。
 */
final class MarketPhaseService
{
    /** フェーズ抽選の重み（合計100）。calm を厚めに、crash を稀に。 */
    private const WEIGHTS = ['calm' => 50, 'boom' => 20, 'storm' => 20, 'crash' => 10];

    public function __construct(private readonly WorldStateRepository $worldStates) {}

    /** 現在の世界状態を返す（必要ならフェーズを遷移させて永続化）。 */
    public function resolve(DateTimeImmutable $now): WorldState
    {
        $state = $this->worldStates->get();
        if ($state->shouldShift($now)) {
            $next = $now->add(new DateInterval('PT' . random_int(Game::PHASE_MIN_SEC, Game::PHASE_MAX_SEC) . 'S'));
            $state->shiftTo($this->pickPhase(), $next, $now);
            $this->worldStates->save($state);
        }
        return $state;
    }

    private function pickPhase(): string
    {
        $roll = random_int(1, 100);
        $acc = 0;
        foreach (self::WEIGHTS as $phase => $weight) {
            $acc += $weight;
            if ($roll <= $acc) {
                return $phase;
            }
        }
        return 'calm';
    }
}
