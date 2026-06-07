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
        private string $passwordHash,
        private int $money,
        public readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $emailVerifiedAt = null,
        public readonly bool $isBot = false,
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
        return password_verify($password, $this->passwordHash);
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    /** パスワードを再設定する（再設定フローから呼ぶ。引数はハッシュ化済み）。 */
    public function changePassword(string $newPasswordHash): void
    {
        $this->passwordHash = $newPasswordHash;
    }
}
