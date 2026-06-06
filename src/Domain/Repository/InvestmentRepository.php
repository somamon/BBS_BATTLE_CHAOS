<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Investment;

interface InvestmentRepository
{
    public function insert(Investment $investment): void;
}
