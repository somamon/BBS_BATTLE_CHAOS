<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\ContactMessage;
use App\Domain\Repository\ContactMessageRepository;

final class InMemoryContactMessageRepository implements ContactMessageRepository
{
    /** @var array<string, ContactMessage> */
    public array $messages = [];

    public function insert(ContactMessage $message): void
    {
        $this->messages[$message->id] = $message;
    }

    public function recent(int $limit = 100): array
    {
        $all = array_values($this->messages);
        usort($all, static fn (ContactMessage $a, ContactMessage $b): int => $b->createdAt <=> $a->createdAt);
        return array_slice($all, 0, $limit);
    }

    public function setStatus(string $id, string $status): void
    {
        $m = $this->messages[$id] ?? null;
        if ($m !== null) {
            $this->messages[$id] = new ContactMessage($m->id, $m->name, $m->email, $m->message, $m->userId, $m->ip, $status, $m->createdAt);
        }
    }

    public function countOpen(): int
    {
        return count(array_filter($this->messages, static fn (ContactMessage $m): bool => $m->status === 'open'));
    }
}
