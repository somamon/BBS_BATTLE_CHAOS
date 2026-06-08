<?php

declare(strict_types=1);

namespace App\Application\UseCase\Thread;

use App\Application\Service\DecayRate;
use App\Domain\Repository\HoldingRepository;
use App\Domain\Repository\PostRepository;
use App\Domain\Repository\ThreadRepository;
use DateTimeImmutable;

/**
 * スレッド詳細。板の減衰を確定し、生存レスを株価・レベル・評価額付きで返す（doc21 §6）。
 * 各投稿が投資対象なので、株価（スポット）・レベル・自分の保有株/評価額を添える。
 */
final class ShowThread
{
    private const LEVEL_LABELS = ['新規', '注目', '人気', '殿堂入り'];

    public function __construct(
        private readonly DecayRate $decay,
        private readonly ThreadRepository $threads,
        private readonly PostRepository $posts,
        private readonly HoldingRepository $holdings,
    ) {}

    /** @return array<string,mixed>|null 見つからなければ null */
    public function execute(string $threadId, ?string $viewerId = null, ?DateTimeImmutable $now = null): ?array
    {
        $now ??= new DateTimeImmutable();

        $thread = $this->threads->findById($threadId);
        if ($thread === null || $thread->isHidden()) {
            return null; // 運営が非表示にしたスレは公開側からは存在しない扱い
        }

        $multiplier = $this->decay->multiplier($now);
        $thread->settleDecay($now, $multiplier);
        $this->threads->save($thread);

        // 生存スレは書き込み・投資可。朽ちたスレ（過去ログ）は閲覧のみ。
        $writable = $thread->isAlive();

        // 閲覧者の保有株を投稿IDで引けるよう先に集約。
        $myShares = [];
        if ($viewerId !== null) {
            foreach ($this->holdings->findByUser($viewerId) as $h) {
                $myShares[$h->postId] = $h->shares();
            }
        }

        // 生存スレは生存レスのみ。過去ログは朽ちたレスも含めて全部見せる。
        $source = $writable
            ? $this->posts->findAliveByThread($threadId)
            : $this->posts->findByThread($threadId);

        $posts = [];
        foreach ($source as $post) {
            $hp = $post->currentHp($now, $multiplier);
            // 生存スレでは朽ちたレスは隠す（従来通り）。過去ログでは隠さない。
            if ($writable && $hp <= 0) {
                continue;
            }
            $isDeadPost = $hp <= 0 || !$post->isAlive();
            $shares = $myShares[$post->id] ?? 0;
            $posts[] = [
                'id'            => $post->id,
                'authorHash'    => $post->authorHash,
                'authorId'      => $post->authorId,
                'content'       => $post->content,
                'hp'            => max(0, $hp),
                'maxHp'         => $post->maxHp(),
                'level'         => $post->level(),
                'levelLabel'    => self::LEVEL_LABELS[$post->level()] ?? '新規',
                'price'         => round($post->spotPrice(), 2),
                'totalInvested' => $post->totalInvested(),
                'totalShares'   => $post->totalShares(),
                'myShares'      => $shares,
                'myValuation'   => $shares > 0 ? $post->valuation($shares, $now, $multiplier) : 0,
                'dead'          => $isDeadPost,
                'createdAt'     => $post->createdAt->format('Y-m-d H:i'),
            ];
        }

        return [
            'thread' => [
                'id'        => $thread->id,
                'title'     => $thread->title,
                'hp'        => $thread->hp(),
                'maxHp'     => $thread->maxHp(),
                'postCount' => $thread->postCount(),
                'status'    => $thread->status(),
                'writable'  => $writable,
                'createdAt' => $thread->createdAt->format('Y-m-d H:i'),
            ],
            'posts' => $posts,
        ];
    }
}
