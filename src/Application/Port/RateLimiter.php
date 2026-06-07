<?php

declare(strict_types=1);

namespace App\Application\Port;

/**
 * レート制限ポート。キー単位で一定時間内の試行回数を数え、上限超過を判定する。
 * ブルートフォース・大量アカウント作成（ボット）対策に使う。
 */
interface RateLimiter
{
    /**
     * 上限に達しているか（試行を消費せずに確認）。
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool;

    /**
     * 試行を1回加算する。ウィンドウが無ければ decaySeconds で新規作成する。
     */
    public function hit(string $key, int $decaySeconds): void;

    /**
     * 成功時などにカウンタを消去する。
     */
    public function clear(string $key): void;

    /**
     * 失効済みのカウンタ行を削除する（テーブル肥大の防止）。
     */
    public function purgeExpired(): void;
}
