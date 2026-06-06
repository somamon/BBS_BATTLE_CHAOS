<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;

/**
 * PDO 接続の生成。DI コンテナで PDO を singleton として供給するためのファクトリ。
 * 接続情報は docker-compose が php サービスに渡す環境変数から取得する。
 */
final class Database
{
    public static function connect(): PDO
    {
        $host = getenv('DB_HOST') ?: 'db';
        $name = getenv('DB_NAME') ?: 'bbs';
        $user = getenv('DB_USER') ?: 'bbs';
        $pass = getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
}
