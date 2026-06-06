<?php

declare(strict_types=1);

namespace App\Application\UseCase\Thread;

use App\Application\Service\MarketPhaseService;
use App\Domain\Repository\PostRepository;
use App\Domain\Repository\ThreadRepository;
use DateTimeImmutable;

/**
 * スレッド詳細。スレッドの減衰を確定し、生存レスを現在HP付きで返す。
 */
final class ShowThread
{
    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly ThreadRepository $threads,
        private readonly PostRepository $posts,
    ) {}

    /** @return array<string,mixed>|null 見つからなければ null */
    public function execute(string $threadId, ?DateTimeImmutable $now = null): ?array
    {
        $now ??= new DateTimeImmutable();

        $thread = $this->threads->findById($threadId);
        if ($thread === null) {
            return null;
        }

        $multiplier = $this->market->resolve($now)->multiplier();
        $thread->settleDecay($now, $multiplier);
        $this->threads->save($thread);

        $posts = [];
        foreach ($this->posts->findAliveByThread($threadId) as $post) {
            $hp = $post->currentHp($now, $multiplier);
            if ($hp <= 0) {
                continue;
            }
            $posts[] = [
                'id'         => $post->id,
                'authorHash' => $post->authorHash,
                'authorId'   => $post->authorId,
                'content'    => $post->content,
                'hp'         => $hp,
                'createdAt'  => $post->createdAt->format('Y-m-d H:i'),
            ];
        }

        return [
            'thread' => [
                'id'            => $thread->id,
                'title'         => $thread->title,
                'hp'            => $thread->hp(),
                'maxHp'         => $thread->maxHp(),
                'mutationLevel' => $thread->mutationLevel(),
                'totalShares'   => $thread->totalShares(),
                'postCount'     => $thread->postCount(),
                'status'        => $thread->status(),
                'createdAt'     => $thread->createdAt->format('Y-m-d H:i'),
            ],
            'posts' => $posts,
        ];
    }
}
