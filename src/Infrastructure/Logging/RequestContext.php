<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

/**
 * リクエスト単位の相関ID（correlation id）と開始時刻を保持する。M4。
 * 1リクエストのすべてのログ行に同じIDが付くことで、ログを横断して追跡できる。
 *
 * 静的に保持するのは「1プロセス=1リクエスト」（PHP-FPM/CLI）前提のため。
 */
final class RequestContext
{
    private static ?string $id = null;
    private static ?float $startedAt = null;

    /** リクエスト開始時に1回呼ぶ。$id を渡さなければ生成する。 */
    public static function init(?string $id = null): string
    {
        self::$id = ($id !== null && $id !== '') ? $id : self::generate();
        self::$startedAt = microtime(true);
        return self::$id;
    }

    /** 現在の相関ID（未初期化なら生成）。 */
    public static function id(): string
    {
        return self::$id ??= self::generate();
    }

    /** リクエスト開始からの経過ミリ秒。 */
    public static function elapsedMs(): int
    {
        if (self::$startedAt === null) {
            return 0;
        }
        return (int) round((microtime(true) - self::$startedAt) * 1000);
    }

    private static function generate(): string
    {
        return bin2hex(random_bytes(8)); // 64bit hex
    }
}
