<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exception\AuthException;
use App\Application\UseCase\Auth\VerifyEmail;
use App\Domain\Entity\EmailVerification;
use App\Domain\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ImmediateTransactionManager;
use Tests\Fake\InMemoryEmailVerificationRepository;
use Tests\Fake\InMemoryUserRepository;

final class VerifyEmailTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryEmailVerificationRepository $verifications;
    private VerifyEmail $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now           = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->users         = new InMemoryUserRepository();
        $this->verifications = new InMemoryEmailVerificationRepository();
        $this->useCase = new VerifyEmail(
            new ImmediateTransactionManager(),
            $this->users,
            $this->verifications,
        );
    }

    private function addUnverifiedUser(string $id = 'u1'): User
    {
        $user = new User($id, $id . '@e.com', 'n', 'x', 500, $this->now, null);
        $this->users->insert($user);
        return $user;
    }

    public function testVerifiesUserAndConsumesToken(): void
    {
        $user = $this->addUnverifiedUser();
        [$ev, $raw] = EmailVerification::issue($user->id, $this->now);
        $this->verifications->insert($ev);

        $result = $this->useCase->execute($raw, $this->now);

        self::assertTrue($result->isEmailVerified());
        self::assertTrue($this->users->findById('u1')->isEmailVerified());
        // トークンは使い切りで破棄される
        self::assertNull($this->verifications->findByTokenHash($ev->tokenHash));
    }

    public function testExpiredTokenThrows(): void
    {
        $user = $this->addUnverifiedUser();
        $expired = new EmailVerification(
            tokenHash: EmailVerification::hashToken('rawtoken'),
            userId: $user->id,
            expiresAt: $this->now->modify('-1 second'),
            createdAt: $this->now->modify('-1 day'),
        );
        $this->verifications->insert($expired);

        $this->expectException(AuthException::class);
        $this->useCase->execute('rawtoken', $this->now);
    }

    public function testUnknownTokenThrows(): void
    {
        $this->expectException(AuthException::class);
        $this->useCase->execute('does-not-exist', $this->now);
    }

    public function testEmptyTokenThrows(): void
    {
        $this->expectException(AuthException::class);
        $this->useCase->execute('   ', $this->now);
    }
}
