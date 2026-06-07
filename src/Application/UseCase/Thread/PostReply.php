<?php

declare(strict_types=1);

namespace App\Application\UseCase\Thread;

use App\Application\Exception\BoardException;
use App\Application\Port\TransactionManager;
use App\Application\Service\DecayRate;
use App\Config\Game;
use App\Domain\Entity\Post;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\PostRepository;
use App\Domain\Repository\ThreadRepository;
use DateTimeImmutable;

/**
 * スレッドへの返信投稿。現在の相場で減衰を確定し、生存スレッドにのみ書き込む。
 * 減衰確定・レス挿入・投稿数加算を単一トランザクションで原子的に行う。
 */
final class PostReply
{
    public function __construct(
        private readonly TransactionManager $tx,
        private readonly DecayRate $decay,
        private readonly ThreadRepository $threads,
        private readonly PostRepository $posts,
    ) {}

    public function execute(string $threadId, string $authorHash, ?string $authorId, string $content): void
    {
        $now = new DateTimeImmutable();

        $this->tx->run(function () use ($threadId, $authorHash, $authorId, $content, $now): void {
            $thread = $this->threads->findByIdForUpdate($threadId);
            if ($thread === null) {
                throw BoardException::threadNotFound();
            }

            $multiplier = $this->decay->multiplier($now);
            $thread->settleDecay($now, $multiplier);
            if (!$thread->isAlive()) {
                $this->threads->save($thread); // 朽ちた事実は確定させる
                throw BoardException::threadDead();
            }

            $content = trim($content);
            if ($content === '') {
                throw ValidationException::field('content', 'validation.content.required', '本文を入力してください');
            }
            if (mb_strlen($content) > Game::POST_CONTENT_MAX) {
                throw ValidationException::field('content', 'validation.content.too_long', '本文は' . Game::POST_CONTENT_MAX . '文字以内にしてください');
            }

            $post = Post::create($threadId, $authorHash, $authorId, $content, $now);
            $this->posts->insert($post);

            $thread->incrementPostCount($now);
            $this->threads->save($thread);
        });
    }
}
