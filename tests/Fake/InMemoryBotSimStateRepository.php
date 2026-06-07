<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Repository\BotSimStateRepository;
use DateTimeImmutable;

final class InMemoryBotSimStateRepository implements BotSimStateRepository
{
    public function __construct(private DateTimeImmutable $lastTick) {}

    public function tryClaim(DateTimeImmutable $now, int $minIntervalSeconds): ?DateTimeImmutable
    {
        if ($now->getTimestamp() - $this->lastTick->getTimestamp() < $minIntervalSeconds) {
            return null;
        }
        $prev = $this->lastTick;
        $this->lastTick = $now;
        return $prev;
    }
}
