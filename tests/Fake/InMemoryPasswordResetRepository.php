<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\PasswordReset;
use App\Domain\Repository\PasswordResetRepository;

final class InMemoryPasswordResetRepository implements PasswordResetRepository
{
    /** @var array<string, PasswordReset> key = tokenHash */
    private array $byHash = [];

    public function insert(PasswordReset $reset): void
    {
        $this->byHash[$reset->tokenHash] = $reset;
    }

    public function findByTokenHash(string $tokenHash): ?PasswordReset
    {
        return $this->byHash[$tokenHash] ?? null;
    }

    public function deleteForUser(string $userId): void
    {
        $this->byHash = array_filter(
            $this->byHash,
            static fn (PasswordReset $r): bool => $r->userId !== $userId,
        );
    }

    public function purgeExpired(\DateTimeImmutable $now): void
    {
        $this->byHash = array_filter(
            $this->byHash,
            static fn (PasswordReset $r): bool => !$r->isExpired($now),
        );
    }
}
