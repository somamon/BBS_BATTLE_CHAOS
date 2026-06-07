<?php

declare(strict_types=1);

namespace App\Config;

/**
 * ゲームバランス定数の一元管理（設計: docs/design/21 §3）。
 * 数値はすべてここから参照し、マジックナンバーをコードに散らさない。
 */
final class Game
{
    // お金
    public const INITIAL_MONEY = 500;
    public const MIN_INVEST    = 1;

    // 投資額の配分（株取得 / HP回復）。配当・sink は廃止（doc21 §2.3）。
    public const SPLIT_SHARES = 0.70;
    public const SPLIT_HP     = 0.30;

    // ボンディングカーブ（株価 = BASE + SLOPE × total_invested）
    public const SHARE_PRICE_BASE  = 10;
    public const SHARE_PRICE_SLOPE = 0.01;

    // 投稿レベル（注目/人気/殿堂入りに必要な total_invested）
    public const POST_LEVEL_TIERS = [100, 1000, 10000];
    // 各レベル（0=新規 / 1=注目 / 2=人気 / 3=殿堂入り）の max_hp
    public const POST_LEVEL_MAX_HP = [100, 300, 800, 2000];

    // レス（投稿）耐久
    public const POST_INIT_HP       = 100;
    public const POST_DECAY_PER_MIN = 5;

    // スレッド（板＝コンテナ）耐久。投資対象ではなく寿命のみ持つ。
    public const THREAD_INIT_HP       = 300;
    public const THREAD_MAX_HP        = 1000;
    public const THREAD_DECAY_PER_MIN = 5;

    // 世界フェーズ（相場天候）→ 減衰倍率
    public const PHASES = [
        'boom'  => 0.7,
        'calm'  => 1.0,
        'storm' => 1.8,
        'crash' => 2.5,
    ];
    public const PHASE_MIN_SEC = 180;
    public const PHASE_MAX_SEC = 900;

    // 人口による減衰スピード調整：人（人間）が少ないうちは朽ちを遅くし、集まるほど通常速度へ。
    public const DECAY_MIN_FACTOR     = 0.3;  // 人がいない時の減衰倍率（通常の30%＝ゆっくり）
    public const DECAY_FULL_AT_HUMANS = 20;   // この人数で通常速度(×1.0)に到達

    // NPC投資家（ボット）。ソロ/少人数を成立させる擬似他人。cron不要の遅延シミュレーション。
    public const BOT_MAX_HUMANS   = 50;   // 人間ユーザーがこの数以下のときだけ稼働
    public const BOT_TICK_SECONDS = 30;   // 何秒の経過ごとに1アクション
    public const BOT_MAX_BURST    = 4;    // 1リクエストで実行する最大アクション数（暴走防止）
    public const BOT_MIN_INVEST   = 30;   // ボット1回の最小投資額（株を1株以上取れる程度）
    public const BOT_MAX_INVEST   = 150;  // ボット1回の最大投資額

    /** フェーズ名 → 減衰倍率（未知は平常 1.0）。 */
    public static function phaseMultiplier(string $phase): float
    {
        return self::PHASES[$phase] ?? 1.0;
    }

    /**
     * 人口（人間の人数）に応じた減衰スピード係数（DECAY_MIN_FACTOR〜1.0）。
     * 0人で最も遅く、DECAY_FULL_AT_HUMANS 人で通常速度（1.0）に線形到達する。
     */
    public static function populationDecayFactor(int $humanCount): float
    {
        $ramp = min(1.0, max(0, $humanCount) / self::DECAY_FULL_AT_HUMANS);
        return self::DECAY_MIN_FACTOR + (1.0 - self::DECAY_MIN_FACTOR) * $ramp;
    }

    /** 累計投資額からスポット株価を求める（後から買うほど高い）。 */
    public static function sharePrice(int $totalInvested): float
    {
        return self::SHARE_PRICE_BASE + self::SHARE_PRICE_SLOPE * $totalInvested;
    }

    /** total_invested から到達している投稿レベル（0..3）を求める。 */
    public static function postLevelFor(int $totalInvested): int
    {
        $level = 0;
        foreach (self::POST_LEVEL_TIERS as $i => $required) {
            if ($totalInvested >= $required) {
                $level = $i + 1;
            }
        }
        return $level;
    }

    /** 投稿レベル → max_hp。 */
    public static function postMaxHpFor(int $level): int
    {
        return self::POST_LEVEL_MAX_HP[$level] ?? self::POST_LEVEL_MAX_HP[0];
    }
}
