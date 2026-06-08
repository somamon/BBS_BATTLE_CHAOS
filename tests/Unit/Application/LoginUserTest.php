<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exception\AuthException;
use App\Application\UseCase\Auth\LoginUser;
use App\Domain\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\InMemoryUserRepository;

final class LoginUserTest extends TestCase
{
    private InMemoryUserRepository $users;
    private LoginUser $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now     = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->users   = new InMemoryUserRepository();
        $this->useCase = new LoginUser($this->users);
    }

    private function addUser(string $email, string $password, bool $verified): void
    {
        $this->users->insert(new User(
            id: 'u_' . $email,
            email: $email,
            name: 'name',
            passwordHash: password_hash($password, PASSWORD_DEFAULT),
            money: 500,
            createdAt: $this->now,
            emailVerifiedAt: $verified ? $this->now : null,
        ));
    }

    public function testLogsInVerifiedUser(): void
    {
        $this->addUser('a@e.com', 'password1', verified: true);
        $user = $this->useCase->execute('a@e.com', 'password1');
        self::assertSame('a@e.com', $user->email);
    }

    public function testWrongPasswordThrows(): void
    {
        $this->addUser('a@e.com', 'password1', verified: true);
        $this->expectException(AuthException::class);
        $this->useCase->execute('a@e.com', 'wrongpass');
    }

    public function testUnknownEmailThrows(): void
    {
        $this->expectException(AuthException::class);
        $this->useCase->execute('nobody@e.com', 'password1');
    }

    public function testUnverifiedUserIsBlocked(): void
    {
        $this->addUser('a@e.com', 'password1', verified: false);
        $this->expectException(AuthException::class);
        $this->useCase->execute('a@e.com', 'password1');
    }

    public function testMalformedEmailThrowsInvalidCredentials(): void
    {
        $this->expectException(AuthException::class);
        $this->useCase->execute('not-an-email', 'password1');
    }

    public function testSuspendedUserIsBlocked(): void
    {
        $this->addUser('a@e.com', 'password1', verified: true);
        $user = $this->users->findByEmail('a@e.com');
        $user->suspend();
        $this->users->save($user);

        $this->expectException(AuthException::class);
        $this->useCase->execute('a@e.com', 'password1');
    }
}
