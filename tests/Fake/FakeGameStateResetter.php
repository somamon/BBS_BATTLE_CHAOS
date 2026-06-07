<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Application\Port\GameStateResetter;

final class FakeGameStateResetter implements GameStateResetter
{
    public int $calls = 0;
    public ?int $lastHumanMoney = null;

    public function reset(int $humanMoney): void
    {
        $this->calls++;
        $this->lastHumanMoney = $humanMoney;
    }
}
