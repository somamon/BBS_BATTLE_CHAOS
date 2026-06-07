<?php

declare(strict_types=1);

/**
 * ラウンド管理 CLI（M2）。終局していれば「ランキング確定→初期化→新ラウンド開始」を行う。
 *
 * 使い方:
 *   php bin/round.php          # 終局しているときだけリセット（cron 向け）
 *   php bin/round.php --force  # 終局していなくても強制的にリセット（運用判断・βの仕切り直し用）
 *
 * 破壊的処理（投稿・スレ・保有株・投資ログを削除）。使い捨て/本番DBを取り違えないこと。
 * Web リクエストからは絶対に実行しない（公開GETでの誤発火を避けるため CLI 専用）。
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Application\UseCase\Endgame\FinalizeAndResetRound;
use App\Config\Environment;
use App\Infrastructure\Container;
use App\Infrastructure\Logging\RequestContext;

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Tokyo');
RequestContext::init();

$force = in_array('--force', $argv, true);

try {
    $container = Container::build();
    /** @var FinalizeAndResetRound $useCase */
    $useCase = $container->get(FinalizeAndResetRound::class);
    $result  = $useCase->execute($force);
} catch (\Throwable $e) {
    fwrite(STDERR, '[round] 失敗: ' . $e->getMessage() . "\n");
    exit(1);
}

if (!$result['reset']) {
    fwrite(STDOUT, "[round] 終局していないためリセットしません（--force で強制実行できます）。\n");
    exit(0);
}

fwrite(STDOUT, sprintf(
    "[round] リセット完了。ラウンド #%s を終局（理由: %s）→ #%s を開始。env=%s\n",
    (string) ($result['endedRound'] ?? '-'),
    (string) ($result['reason'] ?? '-'),
    (string) ($result['newRound'] ?? '-'),
    Environment::appEnv(),
));
exit(0);
