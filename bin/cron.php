<?php

declare(strict_types=1);

/**
 * ゲーム進行 cron エントリポイント。1回の実行で「NPCシミュレーション＋期限切れ掃除＋
 * 終局していればラウンド自動リセット」をまとめて行う（GameTick）。
 *
 * Web トラフィックに依存せず進行させたい本番向け。crontab 例（毎分）:
 *   * * * * * cd /path/to/app && php bin/cron.php >> var/cron.log 2>&1
 *
 * このとき .env で GAME_TICK_DRIVER=cron にすると、公開GETでの相乗り進行を止められる
 * （二重実行はNPCの占有ロックで防がれるが、無駄なDBアクセスを避けられる）。
 *
 * NPCティックは GAME_BOT_TICK_SECONDS（既定30秒）で間隔制御される。毎分cronなら最大1回占有する。
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Application\Service\GameTick;
use App\Infrastructure\Container;
use App\Infrastructure\Logging\RequestContext;

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Tokyo');
RequestContext::init();

try {
    $container = Container::build();
    /** @var GameTick $tick */
    $tick = $container->get(GameTick::class);
    $tick->run();
} catch (\Throwable $e) {
    fwrite(STDERR, '[cron] 失敗: ' . $e->getMessage() . "\n");
    exit(1);
}

exit(0);
