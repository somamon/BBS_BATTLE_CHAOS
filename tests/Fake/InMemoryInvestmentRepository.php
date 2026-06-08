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

    public function deleteForUser(string $userId): void
    {
        $this->records = array_values(array_filter(
            $this->records,
            static fn (Investment $i): bool => $i->investorId !== $userId,
        ));
    }

    public function count(): int
    {
        return count($this->records);
    }
}
