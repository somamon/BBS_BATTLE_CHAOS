<?php

declare(strict_types=1);

namespace App\Application\UseCase\Endgame;

use App\Application\Service\DecayRate;
use App\Config\Game;
use App\Domain\Repository\ThreadRepository;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * 終局判定。生存スレッドが尽きた（all_dead）、または市場の総所持金が
 * 最低投資額を下回った（no_money）場合に世界の終わりとみなす。
 */
final class EndgameStatus
{
    public function __construct(
        private readonly DecayRate $decay,
        private readonly ThreadRepository $threads,
        private readonly UserRepository $users,
    ) {}

    /** @return array{over:bool,reason:?string} */
    public function execute(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $multiplier = $this->decay->multiplier($now);

        // 生存スレッド（現在HP > 0 のもの）が1件もないか。
        $aliveCount = 0;
        foreach ($this->threads->findAlive(50) as $thread) {
            if ($thread->currentHp($now, $multiplier) > 0) {
                $aliveCount++;
            }
        }
        if ($aliveCount === 0) {
            return ['over' => true, 'reason' => 'all_dead'];
        }

        // 市場全体の所持金合計が最低投資額を下回ったか。
        $totalMoney = 0;
        foreach ($this->users->all() as $user) {
            $totalMoney += $user->money();
        }
        if ($totalMoney < Game::MIN_INVEST) {
            return ['over' => true, 'reason' => 'no_money'];
        }

        return ['over' => false, 'reason' => null];
    }
}
