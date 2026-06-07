<?php

declare(strict_types=1);

namespace App\Application\UseCase\Thread;

use App\Domain\Repository\ThreadRepository;

/**
 * 墓場一覧。dead（朽ちて消滅）したスレッドをタイトルのみ閲覧用に返す。
 * 復活不可・本文/レスは見せない（doc20 §2「dead は除外・復活不可」の閲覧専用版）。
 */
final class ListDeadThreads
{
    public function __construct(
        private readonly ThreadRepository $threads,
    ) {}

    /** @return array<int,array<string,mixed>> 朽ちた順のタイトル一覧 */
    public function execute(int $limit = 100): array
    {
        $result = [];
        foreach ($this->threads->findDead($limit) as $thread) {
            $result[] = [
                'id'        => $thread->id,
                'title'     => $thread->title,
                'createdAt' => $thread->createdAt->format('Y-m-d H:i'),
                'diedAt'    => $thread->updatedAt()->format('Y-m-d H:i'),
            ];
        }

        return $result;
    }
}
