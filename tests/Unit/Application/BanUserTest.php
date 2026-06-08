<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Admin\BanUser;
use App\Domain\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeAuditLogger;
use Tests\Fake\InMemoryBanRepository;
use Tests\Fake\InMemoryUserRepository;

final class BanUserTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryBanRepository $bans;
    private FakeAuditLogger $audit;
    private BanUser $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now     = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->users   = new InMemoryUserRepository();
        $this->bans    = new InMemoryBanRepository();
        $this->audit   = new FakeAuditLogger();
        $this->useCase = new BanUser($this->bans, $this->users, $this->audit);
    }

    private function addUser(string $id, bool $isBot = false): void
    {
        $this->users->insert(new User($id, $id . '@e.com', 'n', 'x', 500, $this->now, $this->now, $isBot));
    }

    public function testBanRecordsBanSuspendsAndAudits(): void
    {
        $this->addUser('u1');

        self::assertTrue($this->useCase->ban('admin1', 'u1', '荒らし', '203.0.113.1'));
        self::assertTrue($this->bans->isBanned('user', 'u1'));
        self::assertFalse($this->users->findById('u1')->isActive()); // 凍結も連動
        self::assertCount(1, $this->audit->actions('user.ban'));
    }

    public function testUnbanRemovesBanAndUnsuspends(): void
    {
        $this->addUser('u1');
        $this->useCase->ban('admin1', 'u1');

        self::assertTrue($this->useCase->unban('admin1', 'u1'));
        self::assertFalse($this->bans->isBanned('user', 'u1'));
        self::assertTrue($this->users->findById('u1')->isActive());
        self::assertCount(1, $this->audit->actions('user.unban'));
    }

    public function testCannotBanBot(): void
    {
        $this->addUser('BOT1', isBot: true);
        self::assertFalse($this->useCase->ban('admin1', 'BOT1'));
        self::assertSame([], $this->audit->records);
    }
}
