<?php

declare(strict_types=1);

namespace App\Config;

/**
 * 環境設定の検証。本番（APP_ENV=production）で危険な既定値のまま起動しないよう fail-fast する。
 */
final class Environment
{
    private const WEAK_DB_PASSWORDS = ['', 'bbs', 'root', 'password', 'changeme'];

    public static function appEnv(): string
    {
        return getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production');
    }

    public static function isProduction(): bool
    {
        return self::appEnv() === 'production';
    }

    /**
     * 本番で必須の設定が満たされているか検証する。不足なら例外（起動中止）。
     * 開発・テスト環境では何もしない。
     */
    public static function assertProductionReady(): void
    {
        if (!self::isProduction()) {
            return;
        }

        $errors = [];

        if (in_array((string) (getenv('DB_PASSWORD') ?: ''), self::WEAK_DB_PASSWORDS, true)) {
            $errors[] = 'DB_PASSWORD が未設定または脆弱です';
        }
        if (!str_starts_with((string) (getenv('APP_URL') ?: ''), 'https://')) {
            $errors[] = 'APP_URL を https:// で設定してください';
        }
        if ((getenv('MAIL_DRIVER') ?: 'log') !== 'smtp') {
            $errors[] = 'MAIL_DRIVER=smtp を設定してください（メールが実送信されません）';
        }
        if (strlen((string) (getenv('APP_SECRET') ?: '')) < 16) {
            $errors[] = 'APP_SECRET を16文字以上で設定してください（IPハッシュの匿名化に使用）';
        }

        if ($errors !== []) {
            throw new \RuntimeException('本番設定が不十分です: ' . implode(' / ', $errors));
        }
    }
}
