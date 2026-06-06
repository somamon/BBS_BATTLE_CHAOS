<?php

declare(strict_types=1);

namespace App\Model;

use PDO;

/**
 * PDO 接続を1つだけ生成して共有する簡易ヘルパ。
 * 接続情報は docker-compose で php サービスに渡している環境変数から取得する。
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = getenv('DB_HOST') ?: 'db';
        $name = getenv('DB_NAME') ?: 'bbs';
        $user = getenv('DB_USER') ?: 'bbs';
        $pass = getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return self::$pdo;
    }
}
