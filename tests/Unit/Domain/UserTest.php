<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Config\Game;
use App\Domain\Entity\User;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private function user(int $money = 500, string $passwordHash = 'x'): User
    {
        return new User('u1', 'a@example.com', '名前', $passwordHash, $money, new DateTimeImmutable());
    }

    public function testRegisterGrantsInitialMoney(): void
    {
        $u = User::register('a@example.com', '名前', 'hash', new DateTimeImmutable());
        self::assertSame(Game::INITIAL_MONEY, $u->money());
    }

    public function testCanAfford(): void
    {
        $u = $this->user(100);
        self::assertTrue($u->canAfford(100));
        self::assertFalse($u->canAfford(101));
    }

    public function testDebitReducesMoney(): void
    {
        $u = $this->user(500);
        $u->debit(200);
        self::assertSame(300, $u->money());
    }

    public function testDebitBeyondBalanceThrows(): void
    {
        $u = $this->user(100);
        $this->expectException(DomainException::class);
        $u->debit(101);
    }

    public function testCreditIncreasesMoney(): void
    {
        $u = $this->user(100);
        $u->credit(50);
        self::assertSame(150, $u->money());
    }

    public function testCreditNegativeThrows(): void
    {
        $this->expectException(DomainException::class);
        $this->user()->credit(-1);
    }

    public function testVerifyPassword(): void
    {
        $u = $this->user(passwordHash: password_hash('secret123', PASSWORD_DEFAULT));
        self::assertTrue($u->verifyPassword('secret123'));
        self::assertFalse($u->verifyPassword('wrong'));
    }
}
