<?php

declare(strict_types=1);

namespace App\Application\UseCase\Endgame;

use App\Application\Service\DecayRate;
use App\Config\Game;
use App\Domain\Repository\ThreadRepository;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * 終局判定。生存スレッドが尽きた（all_dead）、または「人間プレイヤーの」総所持金が
 * 最低投資額を下回った（no_money）場合に世界の終わりとみなす。
 *
 * no_money は人間の現金のみで判定する（NPCは資金補充で動く"場の流動性"なので、
 * NPCの所持金で終局が永久に来ないのを防ぐ）。無人（人間0人）のときは終局判定しない。
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

        // 無人（人間0人）なら終局判定しない（無人ワールドでのリセット連発を防ぐ）。
        if ($this->users->countHumans() === 0) {
            return ['over' => false, 'reason' => null];
        }

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

        // 人間プレイヤーの所持金合計が最低投資額を下回ったか（NPCの資金は除外）。
        $humanMoney = 0;
        foreach ($this->users->all() as $user) {
            if (!$user->isBot) {
                $humanMoney += $user->money();
            }
        }
        if ($humanMoney < Game::minInvest()) {
            return ['over' => true, 'reason' => 'no_money'];
        }

        return ['over' => false, 'reason' => null];
    }
}
