<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\Investment;
use App\Domain\Repository\InvestmentRepository;

final class InMemoryInvestmentRepository implements InvestmentRepository
{
    /** @var Investment[] */
    public array $records = [];

    public function insert(Investment $investment): void
    {
        $this->records[] = $investment;
    }
}
