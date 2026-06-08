<?php

declare(strict_types=1);

namespace App\Config;

/**
 * ゲームバランス定数の一元管理（設計: docs/design/21 §3）。
 * 数値はすべてここから参照し、マジックナンバーをコードに散らさない。
 *
 * M3: バランス調整を再デプロイ無しで行えるよう、主要パラメータは環境変数で上書きできる。
 *   - 各 const が「既定値」、対応する GAME_* 環境変数が「上書き値」。
 *   - 値の参照はアクセサメソッド（例 {@see initialMoney()}）経由で行う。
 *   - 環境変数の一覧と意味は docs/ゲームバランス調整.md / .env.example を参照。
 * 既定運用（環境変数なし）では const の値がそのまま使われ、挙動は従来どおり。
 */
final class Game
{
    // ===== 既定値（環境変数が無いときに使う） =====

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

    // 入力長の上限（DBカラム/UIと整合）。※構造的制約のため env 上書き対象外。
    public const THREAD_TITLE_MAX = 255;   // threads.title VARCHAR(255)
    public const POST_CONTENT_MAX = 2000;  // レス本文（textarea maxlength と一致）

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
    public const BOT_REFILL_TO    = 2000; // 資金が尽きたボットを補充する残高（相場の停止防止）

    // 通貨総量の天井。市場全体のお金（全所持金＋全リザーブ）がこれ以上ならNPC補充を止める＝印刷上限。
    public const MONEY_CEILING    = 100000;

    // シーズン（時間制終局）。ラウンド開始(rounds.started_at)からこの秒数が経つと time_up で終局し、
    // ランキングを確定して次シーズンへ。0 以下なら時間制オフ（time_up しない／カウントダウン非表示）。
    public const SEASON_DURATION_SEC = 604800; // 既定 1 週間

    // ===== アクセサ（GAME_* 環境変数で上書き可。未設定なら const を返す） =====

    public static function initialMoney(): int    { return self::envInt('GAME_INITIAL_MONEY', self::INITIAL_MONEY); }
    public static function minInvest(): int        { return self::envInt('GAME_MIN_INVEST', self::MIN_INVEST); }
    public static function splitShares(): float    { return self::envFloat('GAME_SPLIT_SHARES', self::SPLIT_SHARES); }

    public static function postInitHp(): int       { return self::envInt('GAME_POST_INIT_HP', self::POST_INIT_HP); }
    public static function postDecayPerMin(): int  { return self::envInt('GAME_POST_DECAY_PER_MIN', self::POST_DECAY_PER_MIN); }

    public static function threadInitHp(): int     { return self::envInt('GAME_THREAD_INIT_HP', self::THREAD_INIT_HP); }
    public static function threadMaxHp(): int      { return self::envInt('GAME_THREAD_MAX_HP', self::THREAD_MAX_HP); }
    public static function threadDecayPerMin(): int{ return self::envInt('GAME_THREAD_DECAY_PER_MIN', self::THREAD_DECAY_PER_MIN); }

    public static function phaseMinSec(): int      { return self::envInt('GAME_PHASE_MIN_SEC', self::PHASE_MIN_SEC); }
    public static function phaseMaxSec(): int      { return self::envInt('GAME_PHASE_MAX_SEC', self::PHASE_MAX_SEC); }

    public static function botMaxHumans(): int     { return self::envInt('GAME_BOT_MAX_HUMANS', self::BOT_MAX_HUMANS); }
    public static function botTickSeconds(): int   { return self::envInt('GAME_BOT_TICK_SECONDS', self::BOT_TICK_SECONDS); }
    public static function botMaxBurst(): int      { return self::envInt('GAME_BOT_MAX_BURST', self::BOT_MAX_BURST); }
    public static function botMinInvest(): int     { return self::envInt('GAME_BOT_MIN_INVEST', self::BOT_MIN_INVEST); }
    public static function botMaxInvest(): int     { return self::envInt('GAME_BOT_MAX_INVEST', self::BOT_MAX_INVEST); }
    public static function botRefillTo(): int      { return self::envInt('GAME_BOT_REFILL_TO', self::BOT_REFILL_TO); }
    public static function moneyCeiling(): int     { return self::envInt('GAME_MONEY_CEILING', self::MONEY_CEILING); }
    public static function seasonDurationSec(): int { return self::envInt('GAME_SEASON_DURATION_SEC', self::SEASON_DURATION_SEC); }

    /** フェーズ名 → 減衰倍率（未知は平常 1.0）。GAME_PHASE_{BOOM|CALM|STORM|CRASH} で上書き可。 */
    public static function phaseMultiplier(string $phase): float
    {
        $default = self::PHASES[$phase] ?? 1.0;
        if (!isset(self::PHASES[$phase])) {
            return $default;
        }
        return self::envFloat('GAME_PHASE_' . strtoupper($phase), $default);
    }

    /**
     * 人口（人間の人数）に応じた減衰スピード係数（DECAY_MIN_FACTOR〜1.0）。
     * 0人で最も遅く、DECAY_FULL_AT_HUMANS 人で通常速度（1.0）に線形到達する。
     */
    public static function populationDecayFactor(int $humanCount): float
    {
        $minFactor = self::envFloat('GAME_DECAY_MIN_FACTOR', self::DECAY_MIN_FACTOR);
        $fullAt    = self::envInt('GAME_DECAY_FULL_AT_HUMANS', self::DECAY_FULL_AT_HUMANS);
        $fullAt    = max(1, $fullAt);
        $ramp      = min(1.0, max(0, $humanCount) / $fullAt);
        return $minFactor + (1.0 - $minFactor) * $ramp;
    }

    /** 累計投資額からスポット株価を求める（後から買うほど高い）。BASE/SLOPE は env 上書き可。 */
    public static function sharePrice(int $totalInvested): float
    {
        $base  = self::envFloat('GAME_SHARE_PRICE_BASE', (float) self::SHARE_PRICE_BASE);
        $slope = self::envFloat('GAME_SHARE_PRICE_SLOPE', self::SHARE_PRICE_SLOPE);
        return $base + $slope * $totalInvested;
    }

    /** total_invested から到達している投稿レベル（0..3）を求める。閾値は env 上書き可。 */
    public static function postLevelFor(int $totalInvested): int
    {
        $level = 0;
        foreach (self::levelTiers() as $i => $required) {
            if ($totalInvested >= $required) {
                $level = $i + 1;
            }
        }
        return $level;
    }

    /** 投稿レベル → max_hp。テーブルは env 上書き可。 */
    public static function postMaxHpFor(int $level): int
    {
        $table = self::levelMaxHp();
        return $table[$level] ?? $table[0];
    }

    /** @return int[] レベル閾値（GAME_POST_LEVEL_TIERS=カンマ区切り で上書き可）。 */
    private static function levelTiers(): array
    {
        return self::envIntList('GAME_POST_LEVEL_TIERS', self::POST_LEVEL_TIERS);
    }

    /** @return int[] レベル別 max_hp（GAME_POST_LEVEL_MAX_HP=カンマ区切り で上書き可）。 */
    private static function levelMaxHp(): array
    {
        return self::envIntList('GAME_POST_LEVEL_MAX_HP', self::POST_LEVEL_MAX_HP);
    }

    // ===== 値の解決：DB上書き（settings）→ 環境変数 → const 既定 =====

    /** @var array<string,string> 管理画面（settings）からの上書き。起動時に applyOverrides で注入。 */
    private static array $overrides = [];

    /** @param array<string,string> $map settings テーブルの全行（GAME_* を含む）。 */
    public static function applyOverrides(array $map): void
    {
        self::$overrides = $map;
    }

    /** DB上書き → env の順で生値を取り出す（無ければ null）。 */
    private static function raw(string $key): ?string
    {
        if (isset(self::$overrides[$key]) && self::$overrides[$key] !== '') {
            return self::$overrides[$key];
        }
        $v = getenv($key);
        return ($v === false || $v === '') ? null : $v;
    }

    private static function envInt(string $key, int $default): int
    {
        $v = self::raw($key);
        return $v === null ? $default : (int) $v;
    }

    private static function envFloat(string $key, float $default): float
    {
        $v = self::raw($key);
        return $v === null ? $default : (float) $v;
    }

    /**
     * カンマ区切りの整数リストを env から読む。空・不正なら $default。
     * @param int[] $default
     * @return int[]
     */
    private static function envIntList(string $key, array $default): array
    {
        $v = self::raw($key);
        if ($v === null) {
            return $default;
        }
        $out = [];
        foreach (explode(',', $v) as $part) {
            $part = trim($part);
            if ($part !== '') {
                $out[] = (int) $part;
            }
        }
        return $out === [] ? $default : $out;
    }
}
