<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\WorldState;

interface WorldStateRepository
{
    /** 常に1行ある世界状態を取得。 */
    public function get(): WorldState;

    public function save(WorldState $state): void;
}
