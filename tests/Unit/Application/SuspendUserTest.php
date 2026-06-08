<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Admin\SuspendUser;
use App\Domain\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeAuditLogger;
use Tests\Fake\InMemoryUserRepository;

final class SuspendUserTest extends TestCase
{
    private InMemoryUserRepository $users;
    private FakeAuditLogger $audit;
    private SuspendUser $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now     = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->users   = new InMemoryUserRepository();
        $this->audit   = new FakeAuditLogger();
        $this->useCase = new SuspendUser($this->users, $this->audit);
    }

    private function addUser(string $id, bool $isBot = false): void
    {
        $this->users->insert(new User($id, $id . '@e.com', 'n', 'x', 500, $this->now, $this->now, $isBot));
    }

    public function testSuspendAndUnsuspendWithAudit(): void
    {
        $this->addUser('u1');

        self::assertTrue($this->useCase->suspend('admin1', 'u1', '203.0.113.9'));
        self::assertFalse($this->users->findById('u1')->isActive());
        self::assertCount(1, $this->audit->actions('user.suspend'));

        self::assertTrue($this->useCase->unsuspend('admin1', 'u1'));
        self::assertTrue($this->users->findById('u1')->isActive());
        self::assertCount(1, $this->audit->actions('user.unsuspend'));
    }

    public function testCannotSuspendBot(): void
    {
        $this->addUser('BOT1', isBot: true);
        self::assertFalse($this->useCase->suspend('admin1', 'BOT1'));
        self::assertSame([], $this->audit->records);
    }

    public function testUnknownUserReturnsFalse(): void
    {
        self::assertFalse($this->useCase->suspend('admin1', 'ghost'));
    }
}
