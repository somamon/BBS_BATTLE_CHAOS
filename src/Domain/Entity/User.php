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
        public readonly string $passwordHash,
        private int $money,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    /** 新規登録ユーザー（初期所持金を付与）。 */
    public static function register(string $email, string $name, string $passwordHash, DateTimeImmutable $now): self
    {
        return new self(
            id: Ulid::generate(),
            email: $email,
            name: $name,
            passwordHash: $passwordHash,
            money: Game::INITIAL_MONEY,
            createdAt: $now,
        );
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
}
