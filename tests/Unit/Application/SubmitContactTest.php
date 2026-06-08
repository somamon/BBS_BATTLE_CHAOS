<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Contact\SubmitContact;
use App\Domain\Exception\ValidationException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeMailer;

final class SubmitContactTest extends TestCase
{
    private FakeMailer $mailer;
    private SubmitContact $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now     = new DateTimeImmutable('2026-01-01 12:00:00');
        $this->mailer  = new FakeMailer();
        $this->useCase = new SubmitContact($this->mailer, 'ops@example.com');
    }

    public function testSendsToOperatorWithReplyAddressAndMessage(): void
    {
        $this->useCase->execute('太郎', 'taro@example.com', 'バグを見つけました', ['ip' => '203.0.113.1', 'locale' => 'ja'], $this->now);

        self::assertCount(1, $this->mailer->sent);
        self::assertSame('ops@example.com', $this->mailer->sent[0]['to']);
        $body = $this->mailer->lastBody();
        self::assertStringContainsString('バグを見つけました', $body);
        self::assertStringContainsString('taro@example.com', $body); // 返信先
        self::assertStringContainsString('太郎', $body);
    }

    public function testAnonymousWhenNoName(): void
    {
        $this->useCase->execute('', 'a@example.com', 'こんにちは', [], $this->now);
        self::assertStringContainsString('(未記入)', $this->mailer->lastBody());
        self::assertStringContainsString('(未ログイン)', $this->mailer->lastBody());
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute('名前', 'not-an-email', 'メッセージ', [], $this->now);
    }

    public function testRejectsEmptyMessage(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute('名前', 'a@example.com', '   ', [], $this->now);
    }

    public function testRejectsTooLongMessage(): void
    {
        $this->expectException(ValidationException::class);
        $this->useCase->execute('名前', 'a@example.com', str_repeat('あ', 2001), [], $this->now);
    }

    public function testDoesNotSendWhenInvalid(): void
    {
        try {
            $this->useCase->execute('名前', 'bad', 'x', [], $this->now);
        } catch (ValidationException) {
        }
        self::assertCount(0, $this->mailer->sent);
    }

    public function testStoresToDbWhenRepositoryProvided(): void
    {
        $repo = new \Tests\Fake\InMemoryContactMessageRepository();
        $useCase = new SubmitContact($this->mailer, 'ops@example.com', null, $repo);

        $useCase->execute('太郎', 'taro@example.com', '保存される本文', ['ip' => 'iphash'], $this->now);

        self::assertCount(1, $repo->messages);
        self::assertSame(1, $repo->countOpen());
        self::assertCount(1, $this->mailer->sent); // メールも送る
    }
}
