<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimit;

use App\Application\Port\RateLimiter;
use PDO;

/**
 * DB バックエンドのレート制限。キー単位で固定ウィンドウのカウンタを持つ。
 * 失効は DB の時計（NOW()）基準で判定し、PHP/DB のクロックずれを避ける。
 */
final class PdoRateLimiter implements RateLimiter
{
    public function __construct(private PDO $pdo) {}

    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT attempts FROM rate_limits WHERE rl_key = ? AND expires_at > NOW()'
        );
        $stmt->execute([$key]);
        $attempts = $stmt->fetchColumn();

        return $attempts !== false && (int) $attempts >= $maxAttempts;
    }

    public function hit(string $key, int $decaySeconds): void
    {
        // ウィンドウが失効していればリセット（attempts=1）、生きていれば +1。
        $stmt = $this->pdo->prepare(
            'INSERT INTO rate_limits (rl_key, attempts, expires_at)
             VALUES (:k, 1, DATE_ADD(NOW(), INTERVAL :d1 SECOND))
             ON DUPLICATE KEY UPDATE
                attempts   = IF(expires_at < NOW(), 1, attempts + 1),
                expires_at = IF(expires_at < NOW(), DATE_ADD(NOW(), INTERVAL :d2 SECOND), expires_at)'
        );
        $stmt->bindValue(':k', $key);
        $stmt->bindValue(':d1', $decaySeconds, PDO::PARAM_INT);
        $stmt->bindValue(':d2', $decaySeconds, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function clear(string $key): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM rate_limits WHERE rl_key = ?');
        $stmt->execute([$key]);
    }
}
