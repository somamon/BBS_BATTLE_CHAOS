<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Application\Port\RateLimiter;

/** テスト用：失効を無視し、キー単位の試行回数だけを数える。 */
final class InMemoryRateLimiter implements RateLimiter
{
    /** @var array<string,int> */
    private array $counts = [];

    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return ($this->counts[$key] ?? 0) >= $maxAttempts;
    }

    public function hit(string $key, int $decaySeconds): void
    {
        $this->counts[$key] = ($this->counts[$key] ?? 0) + 1;
    }

    public function clear(string $key): void
    {
        unset($this->counts[$key]);
    }

    public function purgeExpired(): void
    {
        // 失効をモデル化しないフェイクなので no-op。
    }
}
