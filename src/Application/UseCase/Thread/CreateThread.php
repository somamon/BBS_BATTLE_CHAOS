<?php

declare(strict_types=1);

namespace App\Application\UseCase\Thread;

use App\Domain\Entity\Thread;
use App\Domain\Repository\ThreadRepository;
use DateTimeImmutable;

/**
 * スレッド作成。タイトルを検証し、満タンHPの新規スレッドを永続化する。
 */
final class CreateThread
{
    public function __construct(
        private readonly ThreadRepository $threads,
    ) {}

    /** @return string 作成したスレッドの id */
    public function execute(?string $creatorId, string $title): string
    {
        $title = trim($title);
        if ($title === '') {
            throw new \InvalidArgumentException('タイトルを入力してください');
        }

        $thread = Thread::create($creatorId, $title, new DateTimeImmutable());
        $this->threads->insert($thread);

        return $thread->id;
    }
}
