<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Service\VerificationMailSender;
use App\Application\UseCase\Auth\ResendVerification;
use App\Domain\Entity\EmailVerification;
use App\Domain\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeMailer;
use Tests\Fake\InMemoryEmailVerificationRepository;
use Tests\Fake\InMemoryUserRepository;

final class ResendVerificationTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryEmailVerificationRepository $verifications;
    private FakeMailer $mailer;
    private ResendVerification $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now           = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->users         = new InMemoryUserRepository();
        $this->verifications = new InMemoryEmailVerificationRepository();
        $this->mailer        = new FakeMailer();
        $sender = new VerificationMailSender($this->verifications, $this->mailer, 'http://test.local');
        $this->useCase = new ResendVerification($this->users, $sender);
    }

    private function addUser(string $email, bool $verified): User
    {
        $user = new User(
            id: 'u_' . $email,
            email: $email,
            name: 'n',
            passwordHash: 'x',
            money: 500,
            createdAt: $this->now,
            emailVerifiedAt: $verified ? $this->now : null,
        );
        $this->users->insert($user);
        return $user;
    }

    public function testResendsForUnverifiedUser(): void
    {
        $this->addUser('a@e.com', verified: false);

        $this->useCase->execute('a@e.com', $this->now);

        self::assertCount(1, $this->mailer->sent);
        $token = $this->mailer->lastToken();
        self::assertNotNull($token);
        self::assertNotNull($this->verifications->findByTokenHash(EmailVerification::hashToken($token)));
    }

    public function testReissuesAndInvalidatesOldToken(): void
    {
        $user = $this->addUser('a@e.com', verified: false);
        [$old] = EmailVerification::issue($user->id, $this->now);
        $this->verifications->insert($old);

        $this->useCase->execute('a@e.com', $this->now);

        // 古いトークンは破棄され、新しいトークンだけが有効。
        self::assertNull($this->verifications->findByTokenHash($old->tokenHash));
        $newToken = $this->mailer->lastToken();
        self::assertNotNull($this->verifications->findByTokenHash(EmailVerification::hashToken($newToken)));
    }

    public function testDoesNothingForAlreadyVerifiedUser(): void
    {
        $this->addUser('a@e.com', verified: true);
        $this->useCase->execute('a@e.com', $this->now);
        self::assertCount(0, $this->mailer->sent);
    }

    public function testDoesNothingForUnknownEmail(): void
    {
        $this->useCase->execute('nobody@e.com', $this->now);
        self::assertCount(0, $this->mailer->sent);
    }

    public function testSilentlyIgnoresMalformedEmail(): void
    {
        $this->useCase->execute('not-an-email', $this->now);
        self::assertCount(0, $this->mailer->sent);
    }
}
