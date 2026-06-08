<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ContactMessage;

interface ContactMessageRepository
{
    public function insert(ContactMessage $message): void;

    /** @return ContactMessage[] 新しい順の最近のお問い合わせ。 */
    public function recent(int $limit = 100): array;

    public function setStatus(string $id, string $status): void;

    public function countOpen(): int;
}
