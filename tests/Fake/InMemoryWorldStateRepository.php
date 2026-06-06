<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\WorldState;
use App\Domain\Repository\WorldStateRepository;

final class InMemoryWorldStateRepository implements WorldStateRepository
{
    public function __construct(private WorldState $state) {}

    public function get(): WorldState
    {
        return $this->state;
    }

    public function save(WorldState $state): void
    {
        $this->state = $state;
    }
}
