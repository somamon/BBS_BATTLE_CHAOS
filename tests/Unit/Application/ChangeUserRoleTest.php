<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Admin\ChangeUserRole;
use App\Domain\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeAuditLogger;
use Tests\Fake\InMemoryUserRepository;

final class ChangeUserRoleTest extends TestCase
{
    private InMemoryUserRepository $users;
    private FakeAuditLogger $audit;
    private ChangeUserRole $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now     = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->users   = new InMemoryUserRepository();
        $this->audit   = new FakeAuditLogger();
        $this->useCase = new ChangeUserRole($this->users, $this->audit);
        $this->users->insert(new User('u1', 'a@e.com', 'n', 'x', 500, $this->now));
    }

    public function testPromoteAndDemote(): void
    {
        self::assertTrue($this->useCase->execute('cli', 'u1', 'admin'));
        self::assertTrue($this->users->findById('u1')->isAdmin());

        self::assertTrue($this->useCase->execute('cli', 'u1', 'user'));
        self::assertFalse($this->users->findById('u1')->isAdmin());

        self::assertCount(2, $this->audit->actions('user.role'));
    }

    public function testRejectsInvalidRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->useCase->execute('cli', 'u1', 'superadmin');
    }

    public function testUnknownUserReturnsFalse(): void
    {
        self::assertFalse($this->useCase->execute('cli', 'ghost', 'admin'));
    }
}
