<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\EmailVerification;
use App\Domain\Repository\EmailVerificationRepository;

final class InMemoryEmailVerificationRepository implements EmailVerificationRepository
{
    /** @var array<string, EmailVerification> key = tokenHash */
    private array $byHash = [];

    public function insert(EmailVerification $verification): void
    {
        $this->byHash[$verification->tokenHash] = $verification;
    }

    public function findByTokenHash(string $tokenHash): ?EmailVerification
    {
        return $this->byHash[$tokenHash] ?? null;
    }

    public function deleteForUser(string $userId): void
    {
        $this->byHash = array_filter(
            $this->byHash,
            static fn (EmailVerification $v): bool => $v->userId !== $userId,
        );
    }
}
