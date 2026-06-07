<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Service\VerificationMailSender;
use App\Application\UseCase\Auth\RegisterUser;
use App\Domain\Entity\EmailVerification;
use App\Domain\Exception\ValidationException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeMailer;
use Tests\Fake\ImmediateTransactionManager;
use Tests\Fake\InMemoryEmailVerificationRepository;
use Tests\Fake\InMemoryUserRepository;

final class RegisterUserTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryEmailVerificationRepository $verifications;
    private FakeMailer $mailer;
    private RegisterUser $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now           = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->users         = new InMemoryUserRepository();
        $this->verifications = new InMemoryEmailVerificationRepository();
        $this->mailer        = new FakeMailer();
        $sender = new VerificationMailSender($this->verifications, $this->mailer, 'http://test.local');
        $this->useCase = new RegisterUser(
            new ImmediateTransactionManager(),
            $this->users,
            $sender,
            $this->mailer,
        );
    }

    public function testRegistersUnverifiedUserAndSendsVerificationMail(): void
    {
        $this->useCase->execute('Alice@Example.com', '目利き', 'password1', $this->now);

        // メールを正規化して未確認で保存
        $user = $this->users->findByEmail('alice@example.com');
        self::assertNotNull($user);
        self::assertSame('alice@example.com', $user->email);
        self::assertFalse($user->isEmailVerified());
        // 確認メールが1通送られ、リンクが含まれる
        self::assertCount(1, $this->mailer->sent);
        self::assertStringContainsString('http://test.local/verify?token=', $this->mailer->lastBody());
        // メール内トークンが保存済みハッシュと一致する
        $token = $this->mailer->lastToken();
        self::assertNotNull($token);
        $stored = $this->verifications->findByTokenHash(EmailVerification::hashToken($token));
        self::assertNotNull($stored);
        self::assertSame($user->id, $stored->userId);
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute('bad', '名前', 'password1', $this->now);
    }

    public function testRejectsShortPassword(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute('a@e.com', '名前', 'short', $this->now);
    }

    public function testRejectsBlankName(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute('a@e.com', '   ', 'password1', $this->now);
    }

    public function testDuplicateEmailIsEnumerationSafe(): void
    {
        // 1人目：通常登録（確認メール）
        $this->useCase->execute('dup@e.com', '名前', 'password1', $this->now);
        $first = $this->users->findByEmail('dup@e.com');
        self::assertNotNull($first);

        // 2人目：大文字でも同一アドレス。例外を投げず、新規ユーザーも作らない。
        $this->useCase->execute('DUP@e.com', '別名', 'password2', $this->now);

        // ユーザーは増えていない（同一ID）
        self::assertCount(1, $this->users->all());
        self::assertSame($first->id, $this->users->findByEmail('dup@e.com')->id);

        // 2通目は「登録済みのお知らせ」（確認トークンを含まない）
        self::assertCount(2, $this->mailer->sent);
        self::assertStringContainsString('登録済み', $this->mailer->sent[1]['subject']);
        self::assertStringNotContainsString('token=', $this->mailer->sent[1]['body']);
    }
}
