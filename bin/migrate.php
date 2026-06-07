<?php

declare(strict_types=1);

/**
 * マイグレーションランナー（初回・更新の両対応）。
 * database/migrations/*.sql を連番順に走らせ、適用済みは schema_migrations 台帳でスキップする。
 * docker-entrypoint-initdb.d（空ボリューム初回のみ）の代替。本番のスキーマ更新もこれで行う。
 *
 * 使い方: php bin/migrate.php   （DBが起動するまで自動リトライ）
 */

$tz = getenv('APP_TIMEZONE') ?: 'Asia/Tokyo';
date_default_timezone_set($tz);
$offset = (new DateTimeImmutable('now', new DateTimeZone($tz)))->format('P'); // 例 +09:00

$host = getenv('DB_HOST') ?: 'db';
$name = getenv('DB_NAME') ?: 'bbs';
$user = getenv('DB_USER') ?: 'bbs';
$pass = getenv('DB_PASSWORD') ?: '';
$dsn  = "mysql:host={$host};dbname={$name};charset=utf8mb4";

$pdo = null;
for ($attempt = 1; $attempt <= 30; $attempt++) {
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->prepare('SET time_zone = ?')->execute([$offset]); // seed の NOW() を APP_TIMEZONE に合わせる
        break;
    } catch (PDOException $e) {
        fwrite(STDERR, "[migrate] DB接続待機 ({$attempt}/30): {$e->getMessage()}\n");
        sleep(2);
    }
}
if ($pdo === null) {
    fwrite(STDERR, "[migrate] DBへ接続できませんでした\n");
    exit(1);
}

// 適用台帳
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        filename   VARCHAR(255) NOT NULL,
        applied_at DATETIME     NOT NULL,
        PRIMARY KEY (filename)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci'
);

$applied = array_flip(
    $pdo->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN)
);

$files = glob(__DIR__ . '/../database/migrations/*.sql') ?: [];
sort($files);

$record = $pdo->prepare('INSERT INTO schema_migrations (filename, applied_at) VALUES (?, NOW())');

$count = 0;
foreach ($files as $file) {
    $base = basename($file);
    if (isset($applied[$base])) {
        continue;
    }

    $sql = (string) file_get_contents($file);
    // 文を ; で分割して順次実行（文字列リテラル内に ; を含まない前提のSQL）。
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        static function (string $s): bool {
            // 行コメントのみ・空白のみのチャンクは除外
            $withoutComments = preg_replace('/^\s*--.*$/m', '', $s) ?? $s;
            return trim($withoutComments) !== '';
        }
    );

    try {
        foreach ($statements as $stmt) {
            $pdo->exec($stmt);
        }
        $record->execute([$base]);
        fwrite(STDOUT, "[migrate] applied {$base}\n");
        $count++;
    } catch (PDOException $e) {
        fwrite(STDERR, "[migrate] 失敗 {$base}: {$e->getMessage()}\n");
        exit(1);
    }
}

fwrite(STDOUT, "[migrate] 完了。新規適用 {$count} 件。\n");
