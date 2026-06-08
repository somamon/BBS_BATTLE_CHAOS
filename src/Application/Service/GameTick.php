<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\Logger;
use App\Application\UseCase\Endgame\FinalizeAndResetRound;
use DateTimeImmutable;

/**
 * ゲーム進行の1ティック。NPCシミュレーション＋（終局していれば）ラウンド自動リセットを束ねる。
 *
 * Web フォールバック（公開GET）と cron（bin/cron.php）の両方からこれを呼ぶ＝進行ロジックの単一窓口。
 * 終局判定はNPCティックを占有できた周期だけ行う（毎リクエストの全ユーザー走査を避ける）。
 * 例外は飲み込み、画面表示やcronを止めない。
 */
final class GameTick
{
    public function __construct(
        private readonly MarketSimulator $market,
        private readonly FinalizeAndResetRound $finalize,
        private readonly ?Logger $logger = null,
    ) {}

    public function run(?DateTimeImmutable $now = null): void
    {
        $now ??= new DateTimeImmutable();

        // NPC進行＋期限切れ掃除。占有できなければ（間隔未満）終局判定もスキップ。
        try {
            $claimed = $this->market->tick($now);
        } catch (\Throwable $e) {
            $this->logger?->error('market_sim_failed', ['error' => $e->getMessage()]);
            return;
        }
        if (!$claimed) {
            return;
        }

        // 終局していれば自動でラウンドをリセット（人間が現金を使い切った等）。
        try {
            $result = $this->finalize->execute(false, $now);
            if ($result['reset']) {
                $this->logger?->event('round_auto_reset', [
                    'reason'      => $result['reason'],
                    'ended_round' => $result['endedRound'],
                    'new_round'   => $result['newRound'],
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger?->error('auto_reset_failed', ['error' => $e->getMessage()]);
        }
    }
}
