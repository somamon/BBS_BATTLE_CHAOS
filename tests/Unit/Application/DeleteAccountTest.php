<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\User\DeleteAccount;
use App\Domain\Entity\Holding;
use App\Domain\Entity\Investment;
use App\Domain\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ImmediateTransactionManager;
use Tests\Fake\InMemoryHoldingRepository;
use Tests\Fake\InMemoryInvestmentRepository;
use Tests\Fake\InMemoryUserRepository;

final class DeleteAccountTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryInvestmentRepository $investments;
    private InMemoryHoldingRepository $holdings;
    private DeleteAccount $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now         = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->users       = new InMemoryUserRepository();
        $this->investments = new InMemoryInvestmentRepository();
        $this->holdings    = new InMemoryHoldingRepository();
        $this->useCase = new DeleteAccount(
            new ImmediateTransactionManager(),
            $this->users,
            $this->investments,
            $this->holdings,
        );
    }

    public function testDeletesUserOwnedData(): void
    {
        $this->users->insert(new User('u1', 'a@e.com', 'A', 'x', 500, $this->now));
        $this->users->insert(new User('u2', 'b@e.com', 'B', 'x', 500, $this->now));
        $this->holdings->save(new Holding('u1', 'p1', 3, 30));
        $this->holdings->save(new Holding('u2', 'p1', 5, 50));
        $this->investments->insert(new Investment('i1', 'u1', 'p1', 30, 3, 10.0, 21, 9, $this->now));
        $this->investments->insert(new Investment('i2', 'u2', 'p1', 50, 5, 10.0, 35, 15, $this->now));

        $ok = $this->useCase->execute('u1');

        self::assertTrue($ok);
        self::assertNull($this->users->findById('u1'));
        self::assertNotNull($this->users->findById('u2')); // 他人は無傷
        self::assertSame([], $this->holdings->findByUser('u1'));
        self::assertCount(1, $this->holdings->findByUser('u2'));
        self::assertCount(1, $this->investments->records); // u2 の分だけ残る
        self::assertSame('u2', $this->investments->records[0]->investorId);
    }

    public function testReturnsFalseForUnknownUser(): void
    {
        self::assertFalse($this->useCase->execute('ghost'));
    }
}
