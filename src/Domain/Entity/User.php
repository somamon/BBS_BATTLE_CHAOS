<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Config\Game;
use App\Domain\Support\Ulid;
use DateTimeImmutable;

/**
 * 登録投資家。所持金を持ち、投資・配当でお金が動く。
 */
final class User
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $name,
        private ?string $passwordHash,
        private int $money,
        public readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $emailVerifiedAt = null,
        public readonly bool $isBot = false,
        private ?string $googleSub = null,
        private string $role = 'user',
        private string $status = 'active',
        private ?DateTimeImmutable $suspendedUntil = null,
    ) {}

    /** 新規登録ユーザー（初期所持金を付与。メールは未確認状態で作る）。 */
    public static function register(string $email, string $name, string $passwordHash, DateTimeImmutable $now): self
    {
        return new self(
            id: Ulid::generate(),
            email: $email,
            name: $name,
            passwordHash: $passwordHash,
            money: Game::initialMoney(),
            createdAt: $now,
            emailVerifiedAt: null,
        );
    }

    /**
     * Googleログインで新規作成するユーザー。
     * パスワードは持たず（passwordHash=null）、メールはGoogleが確認済みのため確認済みで作る。
     */
    public static function fromGoogle(string $email, string $name, string $googleSub, DateTimeImmutable $now): self
    {
        return new self(
            id: Ulid::generate(),
            email: $email,
            name: $name,
            passwordHash: null,
            money: Game::initialMoney(),
            createdAt: $now,
            emailVerifiedAt: $now, // Google が確認済みのアドレス
            isBot: false,
            googleSub: $googleSub,
        );
    }

    /** メール確認が完了しているか。 */
    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function emailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    /** メール確認を完了させる（冪等。既に確認済みなら何もしない）。 */
    public function markEmailVerified(DateTimeImmutable $now): void
    {
        $this->emailVerifiedAt ??= $now;
    }

    public function canAfford(int $amount): bool
    {
        return $this->money >= $amount;
    }

    public function debit(int $amount): void
    {
        if ($amount < 0 || $this->money < $amount) {
            throw new \DomainException('残高不足です');
        }
        $this->money -= $amount;
    }

    public function credit(int $amount): void
    {
        if ($amount < 0) {
            throw new \DomainException('不正な金額です');
        }
        $this->money += $amount;
    }

    public function money(): int { return $this->money; }

    public function verifyPassword(string $password): bool
    {
        // パスワード未設定（Googleのみのアカウント）はパスワードログイン不可。
        return $this->passwordHash !== null && password_verify($password, $this->passwordHash);
    }

    public function passwordHash(): ?string
    {
        return $this->passwordHash;
    }

    /** パスワードを再設定する（再設定フローから呼ぶ。引数はハッシュ化済み）。 */
    public function changePassword(string $newPasswordHash): void
    {
        $this->passwordHash = $newPasswordHash;
    }

    public function googleSub(): ?string
    {
        return $this->googleSub;
    }

    // --- 管理（ロール・凍結） ---

    public function role(): string { return $this->role; }

    public function isAdmin(): bool { return $this->role === 'admin'; }

    /** ロールを設定する（管理CLIから昇格/降格に使う）。 */
    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function status(): string { return $this->status; }

    public function isActive(): bool { return $this->status === 'active'; }

    public function suspendedUntil(): ?DateTimeImmutable { return $this->suspendedUntil; }

    /** 凍結する。$until が null なら無期限。 */
    public function suspend(?DateTimeImmutable $until = null): void
    {
        $this->status = 'suspended';
        $this->suspendedUntil = $until;
    }

    /** 凍結を解除する。 */
    public function unsuspend(): void
    {
        $this->status = 'active';
        $this->suspendedUntil = null;
    }

    /** このアカウントを Google 連携する（生成済みアカウントへ sub を結びつけ、メールを確認済みにする）。 */
    public function linkGoogle(string $googleSub, DateTimeImmutable $now): void
    {
        $this->googleSub = $googleSub;
        $this->markEmailVerified($now); // Google が確認済みのアドレス
    }
}
