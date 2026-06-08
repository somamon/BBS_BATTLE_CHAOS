<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\Ban;
use App\Domain\Repository\BanRepository;
use DateTimeImmutable;

final class InMemoryBanRepository implements BanRepository
{
    /** @var array<int, Ban> */
    private array $bans = [];
    private int $seq = 0;

    public function isBanned(string $kind, string $value, ?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable();
        foreach ($this->bans as $b) {
            if ($b->kind === $kind && $b->value === $value
                && ($b->expiresAt === null || $b->expiresAt > $now)) {
                return true;
            }
        }
        return false;
    }

    public function insert(Ban $ban): void
    {
        $id = ++$this->seq;
        $this->bans[$id] = new Ban($id, $ban->kind, $ban->value, $ban->reason, $ban->createdBy, $ban->expiresAt, $ban->createdAt);
    }

    public function listActive(int $limit = 100, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $active = array_values(array_filter($this->bans, static fn (Ban $b): bool => $b->expiresAt === null || $b->expiresAt > $now));
        usort($active, static fn (Ban $a, Ban $b): int => $b->createdAt <=> $a->createdAt);
        return array_slice($active, 0, $limit);
    }

    public function removeById(int $id): void
    {
        unset($this->bans[$id]);
    }

    public function removeByKindValue(string $kind, string $value): void
    {
        foreach ($this->bans as $id => $b) {
            if ($b->kind === $kind && $b->value === $value) {
                unset($this->bans[$id]);
            }
        }
    }
}
