<?php

declare(strict_types=1);

namespace App\Infrastructure\Runtime;

/**
 * 管理ナビの未対応バッジ件数（通報・お問い合わせ）。AdminMiddleware が毎リクエスト設定し、
 * 管理レイアウトが参照する軽量な静的ホルダ。
 */
final class AdminBadges
{
    private static int $reports = 0;
    private static int $contact = 0;

    public static function set(int $reports, int $contact): void
    {
        self::$reports = $reports;
        self::$contact = $contact;
    }

    public static function reports(): int { return self::$reports; }
    public static function contact(): int { return self::$contact; }
}
