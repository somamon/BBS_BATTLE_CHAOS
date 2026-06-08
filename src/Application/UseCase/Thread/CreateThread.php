<?php

declare(strict_types=1);

namespace App\Application\UseCase\Thread;

use App\Config\Game;
use App\Domain\Entity\Thread;
use App\Domain\Exception\ValidationException;
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
    public function execute(?string $creatorId, string $title, string $lang = 'ja', ?DateTimeImmutable $now = null): string
    {
        $now ??= new DateTimeImmutable();
        $title = trim($title);
        if ($title === '') {
            throw ValidationException::field('title', 'validation.title.required', 'タイトルを入力してください');
        }
        if (mb_strlen($title) > Game::THREAD_TITLE_MAX) {
            throw ValidationException::field('title', 'validation.title.too_long', 'タイトルは' . Game::THREAD_TITLE_MAX . '文字以内にしてください');
        }

        $thread = Thread::create($creatorId, $title, $now, $lang);
        $this->threads->insert($thread);

        return $thread->id;
    }
}
