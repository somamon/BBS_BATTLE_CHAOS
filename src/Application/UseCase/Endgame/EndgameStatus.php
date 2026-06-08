<?php

declare(strict_types=1);

namespace App\Application\UseCase\Endgame;

use App\Application\Service\DecayRate;
use App\Config\Game;
use App\Domain\Repository\RoundRepository;
use App\Domain\Repository\ThreadRepository;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * 終局判定。シーズン期間が満了した（time_up）、または生存スレッドが尽きた（all_dead）
 * 場合に世界の終わりとみなす。
 *
 * time_up は時間制シーズンの締め：ラウンド開始(started_at)から Game::seasonDurationSec() 秒が
 * 経過したら終局し、ランキングを確定して次シーズンへ。期間設定が 0 以下なら時間制はオフ。
 * all_dead は退化状態（投資対象が無くなり場が枯れた）の安全弁として残す。
 * 無人（人間0人）のときは終局判定しない（無人ワールドでのリセット連発を防ぐ）。
 */
final class EndgameStatus
{
    public function __construct(
        private readonly DecayRate $decay,
        private readonly ThreadRepository $threads,
        private readonly UserRepository $users,
        private readonly RoundRepository $rounds,
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

        // シーズン期間が満了したか（時間制）。started_at + 期間 <= now で終局。
        $duration = Game::seasonDurationSec();
        if ($duration > 0) {
            $current = $this->rounds->current();
            if ($current !== null) {
                $endsAt = $current->startedAt->modify("+{$duration} seconds");
                if ($now >= $endsAt) {
                    return ['over' => true, 'reason' => 'time_up'];
                }
            }
        }

        // 生存スレッド（現在HP > 0 のもの）が1件もないか（退化状態の安全弁）。
        $aliveCount = 0;
        foreach ($this->threads->findAlive(50) as $thread) {
            if ($thread->currentHp($now, $multiplier) > 0) {
                $aliveCount++;
            }
        }
        if ($aliveCount === 0) {
            return ['over' => true, 'reason' => 'all_dead'];
        }

        return ['over' => false, 'reason' => null];
    }
}
