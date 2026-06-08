<?php

declare(strict_types=1);

namespace App\Infrastructure\Runtime;

/**
 * サイト全体のランタイム状態（アナウンス文・メンテモード）。settings から起動時に注入する。
 * 公開レイアウトや index.php から参照する軽量な静的ホルダ。
 */
final class SiteState
{
    private static ?string $announcement = null;
    private static bool $maintenance = false;

    /** @param array<string,string> $settings settings テーブルの全行。 */
    public static function boot(array $settings): void
    {
        $a = $settings['announcement'] ?? '';
        self::$announcement = trim($a) !== '' ? $a : null;
        self::$maintenance = ($settings['maintenance'] ?? '0') === '1';
    }

    public static function announcement(): ?string
    {
        return self::$announcement;
    }

    public static function isMaintenance(): bool
    {
        return self::$maintenance;
    }
}
