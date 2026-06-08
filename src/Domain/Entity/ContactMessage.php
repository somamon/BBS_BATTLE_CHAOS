<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Support\Ulid;
use DateTimeImmutable;

/**
 * お問い合わせの控え（メール送信に加えてDBにも残す）。
 */
final class ContactMessage
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $name,
        public readonly string $email,
        public readonly string $message,
        public readonly ?string $userId,
        public readonly ?string $ip,
        public readonly string $status,   // open | done
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(?string $name, string $email, string $message, ?string $userId, ?string $ip, DateTimeImmutable $now): self
    {
        return new self(Ulid::generate(), $name, $email, $message, $userId, $ip, 'open', $now);
    }
}
