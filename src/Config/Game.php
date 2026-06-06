<?php

declare(strict_types=1);

namespace App\Config;

/**
 * ゲームバランス定数の一元管理（設計: docs/design/20 §3）。
 * 数値はすべてここから参照し、マジックナンバーをコードに散らさない。
 */
final class Game
{
    // お金
    public const INITIAL_MONEY = 500;

    // 投資額の配分（HP回復 / 既存株主への配当 / 消滅）
    public const SPLIT_HP       = 0.50;
    public const SPLIT_DIVIDEND = 0.30;
    public const SPLIT_SINK     = 0.20;
    public const MIN_INVEST     = 1;

    // スレッド耐久
    public const THREAD_INIT_HP       = 300;
    public const THREAD_MAX_HP        = 1000;
    public const THREAD_DECAY_PER_MIN = 5;

    // レス耐久（投資対象外）
    public const POST_INIT_HP       = 100;
    public const POST_DECAY_PER_MIN = 5;

    // 世界フェーズ（相場天候）→ 減衰倍率
    public const PHASES = [
        'boom'  => 0.7,
        'calm'  => 1.0,
        'storm' => 1.8,
        'crash' => 2.5,
    ];
    public const PHASE_MIN_SEC = 180;
    public const PHASE_MAX_SEC = 900;

    // 変異（化け株）: Lv1/2/3 の到達に必要な total_shares
    public const MUTATION_TIERS = [500, 2000, 8000];
    // 各 Lv（0..3）の max_hp
    public const MUTATION_MAX_HP = [1000, 2000, 4000, 8000];
    // 各 Lv（0..3）の配当ボーナス倍率
    public const MUTATION_DIV_BONUS = [1.0, 1.1, 1.2, 1.3];

    /** フェーズ名 → 減衰倍率（未知は平常 1.0）。 */
    public static function phaseMultiplier(string $phase): float
    {
        return self::PHASES[$phase] ?? 1.0;
    }

    /** total_shares から到達している変異レベル（0..3）を求める。 */
    public static function mutationLevelFor(int $totalShares): int
    {
        $level = 0;
        foreach (self::MUTATION_TIERS as $i => $required) {
            if ($totalShares >= $required) {
                $level = $i + 1;
            }
        }
        return $level;
    }

    /** 変異レベル → max_hp。 */
    public static function maxHpFor(int $mutationLevel): int
    {
        return self::MUTATION_MAX_HP[$mutationLevel] ?? self::THREAD_MAX_HP;
    }

    /** 変異レベル → 配当ボーナス倍率。 */
    public static function dividendBonusFor(int $mutationLevel): float
    {
        return self::MUTATION_DIV_BONUS[$mutationLevel] ?? 1.0;
    }
}
