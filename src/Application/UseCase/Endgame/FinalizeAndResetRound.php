<?php

declare(strict_types=1);

namespace App\Application\UseCase\Endgame;

use App\Application\Port\GameStateResetter;
use App\Application\Port\Logger;
use App\Application\Port\TransactionManager;
use App\Application\UseCase\Ranking\RankingQuery;
use App\Config\Game;
use App\Domain\Repository\RoundRepository;
use DateTimeImmutable;

/**
 * 終局→ランキング確定→初期化→再開（M2 のコア）。
 *
 * 手順（すべて単一トランザクションで原子的に）:
 *  1. 終局しているか判定（force=true なら判定を飛ばして強制実行）
 *  2. 現ラウンドの最終ランキングをスナップショット保存（初期化で消える前に確定）
 *  3. 現ラウンドを終局として確定
 *  4. 遊技データを初期化（アカウントは残す）
 *  5. 新ラウンドを開始
 *
 * 破壊的処理のため Web リクエストからは呼ばず、CLI（bin/round.php）や cron から実行する。
 */
final class FinalizeAndResetRound
{
    public function __construct(
        private readonly TransactionManager $tx,
        private readonly EndgameStatus $endgame,
        private readonly RankingQuery $ranking,
        private readonly RoundRepository $rounds,
        private readonly GameStateResetter $resetter,
        private readonly ?Logger $logger = null,
    ) {}

    /**
     * @return array{reset:bool,reason:?string,endedRound:?int,newRound:?int}
     */
    public function execute(bool $force = false, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        $status = $this->endgame->execute($now);
        if (!$force && !$status['over']) {
            return ['reset' => false, 'reason' => null, 'endedRound' => null, 'newRound' => null];
        }
        $reason = $status['reason'] ?? 'manual';

        return $this->tx->run(function () use ($now, $reason): array {
            $current = $this->rounds->current();

            // 初期化で消える前に、現時点のランキングを確定保存する。
            if ($current !== null && $current->id !== null) {
                $rows = $this->ranking->execute($now);
                $snapshot = [];
                $rankNo = 0;
                foreach ($rows as $r) {
                    $rankNo++;
                    $snapshot[] = [
                        'rank'       => $rankNo,
                        'userId'     => $r['userId'] ?? null,
                        'name'       => $r['name'],
                        'cash'       => $r['money'],
                        'shareValue' => $r['shareValue'],
                        'total'      => $r['total'],
                    ];
                }
                $this->rounds->saveRankings($current->id, $snapshot);
                $this->rounds->end($current->id, $now, $reason);
            }

            // 遊技データを初期化（人間の所持金は初期値へ）。
            $this->resetter->reset(Game::initialMoney());

            // 新ラウンド開始。
            $new = $this->rounds->start($now);

            $this->logger?->event('round_reset', [
                'ended_round' => $current?->id,
                'new_round'   => $new->id,
                'reason'      => $reason,
            ]);

            return [
                'reset'      => true,
                'reason'     => $reason,
                'endedRound' => $current?->id,
                'newRound'   => $new->id,
            ];
        });
    }
}
