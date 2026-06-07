<?php

declare(strict_types=1);

namespace App\Application\Port;

/**
 * 構造化ログのポート（Application 層が依存する抽象）。M4。
 * 実装は1イベント=1行のJSONを出力し、相関ID（リクエストID）を自動付与する。
 *
 * - event(): 主要KPI（登録・投資など）の計測イベント
 * - error()/warning()/info(): 運用ログ
 *
 * @phpstan-type LogContext array<string,scalar|null>
 */
interface Logger
{
    /** @param array<string,mixed> $context */
    public function log(string $level, string $message, array $context = []): void;

    /** KPIイベント。$name はイベント名（例: user_registered, investment_made）。
     *  @param array<string,mixed> $context */
    public function event(string $name, array $context = []): void;

    /** @param array<string,mixed> $context */
    public function error(string $message, array $context = []): void;

    /** @param array<string,mixed> $context */
    public function warning(string $message, array $context = []): void;

    /** @param array<string,mixed> $context */
    public function info(string $message, array $context = []): void;
}
