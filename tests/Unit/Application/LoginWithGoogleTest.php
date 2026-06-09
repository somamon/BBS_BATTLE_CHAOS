<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exception\AuthException;
use App\Application\UseCase\Auth\LoginWithGoogle;
use App\Domain\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ImmediateTransactionManager;
use Tests\Fake\InMemoryUserRepository;

final class LoginWithGoogleTest extends TestCase
{
    private InMemoryUserRepository $users;
    private LoginWithGoogle $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now     = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->users   = new InMemoryUserRepository();
        $this->useCase = new LoginWithGoogle(new ImmediateTransactionManager(), $this->users);
    }

    public function testReturnsExistingLinkedUser(): void
    {
        $linked = User::fromGoogle('a@example.com', '目利き', 'sub-123', $this->now);
        $this->users->insert($linked);

        $result = $this->useCase->execute('sub-123', 'a@example.com', true, 'Google名', $this->now);

        self::assertSame($linked->id, $result->id);
        self::assertCount(1, $this->users->all()); // 新規作成されない
    }

    public function testCreatesNewUserForUnknownSub(): void
    {
        $result = $this->useCase->execute('sub-new', 'New@Example.com', true, '新規さん', $this->now);

        self::assertSame('new@example.com', $result->email); // 正規化
        self::assertSame('sub-new', $result->googleSub());
        self::assertTrue($result->isEmailVerified());
        self::assertNull($result->passwordHash()); // パスワード無し
        self::assertFalse($result->verifyPassword('anything'));
        self::assertNotNull($this->users->findByGoogleSub('sub-new'));
    }

    public function testLinksToExistingEmailAccount(): void
    {
        // 既存のパスワードアカウント（同一メール）。
        $existing = User::register('a@example.com', '既存', password_hash('pass1234', PASSWORD_DEFAULT), $this->now);
        $this->users->insert($existing);

        $result = $this->useCase->execute('sub-xyz', 'a@example.com', true, 'Google名', $this->now);

        self::assertSame($existing->id, $result->id);       // 同一アカウントに連携
        self::assertSame('sub-xyz', $result->googleSub());
        self::assertTrue($result->isEmailVerified());
        self::assertTrue($result->verifyPassword('pass1234')); // 既存パスワードは維持
        self::assertCount(1, $this->users->all());
    }

    public function testRejectsUnverifiedGoogleEmail(): void
    {
        $this->expectException(AuthException::class);
        $this->useCase->execute('sub-1', 'a@example.com', false, '名前', $this->now);
    }

    public function testDoesNotHijackBotAccount(): void
    {
        $bot = new User('BOT1', 'bot@bots.local', 'NPC', 'x', 5000, $this->now, $this->now, true);
        $this->users->insert($bot);

        $this->expectException(AuthException::class);
        $this->useCase->execute('sub-bot', 'bot@bots.local', true, '名前', $this->now);
    }

    public function testUsesAnonymousHandleNotGoogleName(): void
    {
        // Google の表示名(本名想定)もメールのローカル部も既定の公開名には使わず、
        // 匿名ハンドルにする（公開ランキングへの本名漏れ防止。マイページで変更可）。
        $result = $this->useCase->execute('sub-2', 'taro@example.com', true, '山田太郎', $this->now);
        self::assertNotSame('山田太郎', $result->name);
        self::assertNotSame('taro', $result->name);
        self::assertMatchesRegularExpression('/^ユーザー\d+$/u', $result->name);
    }
}
