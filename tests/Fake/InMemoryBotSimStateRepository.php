<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Repository\BotSimStateRepository;
use DateTimeImmutable;

final class InMemoryBotSimStateRepository implements BotSimStateRepository
{
    public function __construct(private DateTimeImmutable $lastTick) {}

    public function getLastTick(): DateTimeImmutable
    {
        return $this->lastTick;
    }

    public function setLastTick(DateTimeImmutable $at): void
    {
        $this->lastTick = $at;
    }
}
