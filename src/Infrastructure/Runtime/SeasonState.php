<?php

declare(strict_types=1);

namespace App\Infrastructure\Runtime;

use DateTimeImmutable;

/**
 * 現シーズン（ラウンド）の終了予定時刻を保持する軽量な静的ホルダ。
 * index.php で現ラウンドの started_at + Game::seasonDurationSec() を計算して boot し、
 * 公開レイアウト（layout.php）がカウントダウン表示のために静的参照する。
 * 時間制オフ（期間0以下）やラウンド未取得のときは null＝非表示。
 *
 * @see SiteState 同じ「起動時注入→レイアウト参照」パターン。
 */
final class SeasonState
{
    private static ?int $endsAtEpoch = null;

    public static function boot(?DateTimeImmutable $endsAt): void
    {
        self::$endsAtEpoch = $endsAt?->getTimestamp();
    }

    /** シーズン終了予定時刻（UNIX秒）。未設定なら null。 */
    public static function endsAtEpoch(): ?int
    {
        return self::$endsAtEpoch;
    }
}
