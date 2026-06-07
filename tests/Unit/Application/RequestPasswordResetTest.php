<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Service\PasswordResetMailSender;
use App\Application\UseCase\Auth\RequestPasswordReset;
use App\Domain\Entity\PasswordReset;
use App\Domain\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeMailer;
use Tests\Fake\InMemoryPasswordResetRepository;
use Tests\Fake\InMemoryUserRepository;

final class RequestPasswordResetTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryPasswordResetRepository $resets;
    private FakeMailer $mailer;
    private RequestPasswordReset $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now    = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->users  = new InMemoryUserRepository();
        $this->resets = new InMemoryPasswordResetRepository();
        $this->mailer = new FakeMailer();
        $sender = new PasswordResetMailSender($this->resets, $this->mailer, 'http://test.local');
        $this->useCase = new RequestPasswordReset($this->users, $sender);
    }

    private function addUser(string $email = 'a@example.com', bool $isBot = false): User
    {
        $user = new User('u1', $email, '目利き', 'x', 500, $this->now, $this->now, $isBot);
        $this->users->insert($user);
        return $user;
    }

    public function testSendsResetMailWithTokenForExistingUser(): void
    {
        $user = $this->addUser();

        $this->useCase->execute('a@example.com', $this->now);

        self::assertCount(1, $this->mailer->sent);
        self::assertStringContainsString('http://test.local/password/reset?token=', $this->mailer->lastBody());
        $token = $this->mailer->lastToken();
        self::assertNotNull($token);
        $stored = $this->resets->findByTokenHash(PasswordReset::hashToken($token));
        self::assertNotNull($stored);
        self::assertSame($user->id, $stored->userId);
    }

    public function testSilentForUnknownEmail(): void
    {
        $this->useCase->execute('nobody@example.com', $this->now);
        self::assertCount(0, $this->mailer->sent);
    }

    public function testSilentForMalformedEmail(): void
    {
        $this->useCase->execute('not-an-email', $this->now);
        self::assertCount(0, $this->mailer->sent);
    }

    public function testNeverSendsToBots(): void
    {
        $this->addUser('bot@bots.local', isBot: true);
        $this->useCase->execute('bot@bots.local', $this->now);
        self::assertCount(0, $this->mailer->sent);
    }

    public function testReissuingInvalidatesOldToken(): void
    {
        $this->addUser();

        $this->useCase->execute('a@example.com', $this->now);
        $firstToken = $this->mailer->lastToken();

        $this->useCase->execute('a@example.com', $this->now->modify('+1 minute'));
        $secondToken = $this->mailer->lastToken();

        self::assertNotSame($firstToken, $secondToken);
        // 旧トークンは無効化され、新トークンのみ有効。
        self::assertNull($this->resets->findByTokenHash(PasswordReset::hashToken($firstToken)));
        self::assertNotNull($this->resets->findByTokenHash(PasswordReset::hashToken($secondToken)));
    }
}
