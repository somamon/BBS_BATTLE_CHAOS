<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Application\Port\Logger;

/**
 * 構造化ログの実装。1イベント=1行のJSONを error_log() へ出力する。M4。
 * error_log は PHP の設定先（本番Dockerでは stderr → コンテナログ）に流れるため、
 * ログ集約基盤（CloudWatch / Loki 等）でそのまま取り込める。
 *
 * すべての行に相関ID（{@see RequestContext::id()}）と環境名・時刻を付与する。
 */
final class JsonLogger implements Logger
{
    public function __construct(private readonly string $env = 'production') {}

    public function log(string $level, string $message, array $context = []): void
    {
        $record = [
            'ts'         => date('c'),
            'level'      => $level,
            'env'        => $this->env,
            'request_id' => RequestContext::id(),
            'msg'        => $message,
        ];
        if ($context !== []) {
            $record['ctx'] = $context;
        }

        $json = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = json_encode([
                'ts'         => date('c'),
                'level'      => 'error',
                'env'        => $this->env,
                'request_id' => RequestContext::id(),
                'msg'        => 'log_encode_failed',
            ]);
        }
        error_log((string) $json);
    }

    public function event(string $name, array $context = []): void
    {
        $this->log('event', $name, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }
}
