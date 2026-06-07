<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exception\AuthException;
use App\Application\UseCase\Auth\ResetPassword;
use App\Domain\Entity\PasswordReset;
use App\Domain\Entity\User;
use App\Domain\Exception\ValidationException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fake\ImmediateTransactionManager;
use Tests\Fake\InMemoryPasswordResetRepository;
use Tests\Fake\InMemoryUserRepository;

final class ResetPasswordTest extends TestCase
{
    private InMemoryUserRepository $users;
    private InMemoryPasswordResetRepository $resets;
    private ResetPassword $useCase;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now    = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->users  = new InMemoryUserRepository();
        $this->resets = new InMemoryPasswordResetRepository();
        $this->useCase = new ResetPassword(new ImmediateTransactionManager(), $this->users, $this->resets);
    }

    /** @return array{0:User,1:string} [user, rawToken] */
    private function userWithToken(): array
    {
        $user = new User('u1', 'a@example.com', '目利き', password_hash('oldpass1', PASSWORD_DEFAULT), 500, $this->now);
        $this->users->insert($user);
        [$reset, $rawToken] = PasswordReset::issue($user->id, $this->now);
        $this->resets->insert($reset);
        return [$user, $rawToken];
    }

    public function testResetsPasswordAndConsumesToken(): void
    {
        [$user, $token] = $this->userWithToken();

        $result = $this->useCase->execute($token, 'newpass123', $this->now);

        self::assertSame($user->id, $result->id);
        // 新パスワードで検証できる／旧パスワードは不可。
        self::assertTrue($this->users->findById('u1')->verifyPassword('newpass123'));
        self::assertFalse($this->users->findById('u1')->verifyPassword('oldpass1'));
        // 受信できた＝アドレス所有の証明としてメール確認も完了。
        self::assertTrue($this->users->findById('u1')->isEmailVerified());
        // トークンは使い捨て。
        self::assertNull($this->resets->findByTokenHash(PasswordReset::hashToken($token)));
    }

    public function testRejectsInvalidToken(): void
    {
        $this->userWithToken();
        $this->expectException(AuthException::class);
        $this->useCase->execute('deadbeef', 'newpass123', $this->now);
    }

    public function testRejectsExpiredToken(): void
    {
        [, $token] = $this->userWithToken();
        $later = $this->now->modify('+' . (PasswordReset::TTL_SECONDS + 1) . ' seconds');
        $this->expectException(AuthException::class);
        $this->useCase->execute($token, 'newpass123', $later);
    }

    public function testRejectsWeakPasswordWithoutConsumingToken(): void
    {
        [, $token] = $this->userWithToken();
        try {
            $this->useCase->execute($token, 'short', $this->now);
            self::fail('ValidationException expected');
        } catch (ValidationException) {
            // ポリシー違反ではトークンを消費しない（再入力できる）。
            self::assertNotNull($this->resets->findByTokenHash(PasswordReset::hashToken($token)));
        }
    }
}
