<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Service\DecayRate;
use App\Domain\Repository\HoldingRepository;
use App\Domain\Repository\PostRepository;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * マイページ。所持金と保有株（投稿ごと）の時価評価・含み損益を返す（doc21 §5）。
 * 株評価 = 保有株数 × スポット株価 × 鮮度。含み損益 = 評価額 − 取得原価。
 */
final class MyPageQuery
{
    /** 本文プレビューの最大文字数。 */
    private const EXCERPT_LEN = 40;

    public function __construct(
        private readonly DecayRate $decay,
        private readonly UserRepository $users,
        private readonly HoldingRepository $holdings,
        private readonly PostRepository $posts,
    ) {}

    /** @return array<string,mixed>|null 見つからなければ null */
    public function execute(string $userId, ?DateTimeImmutable $now = null): ?array
    {
        $now ??= new DateTimeImmutable();

        $user = $this->users->findById($userId);
        if ($user === null) {
            return null;
        }

        $multiplier = $this->decay->multiplier($now);

        $holdings = $this->holdings->findByUser($userId);
        $postMap  = $this->posts->findByIds(array_map(static fn ($h) => $h->postId, $holdings));

        $shareValue = 0;
        $holdingRows = [];
        foreach ($holdings as $holding) {
            if ($holding->shares() <= 0) {
                continue;
            }
            $post = $postMap[$holding->postId] ?? null;
            if ($post === null) {
                continue;
            }
            $valuation = $post->valuation($holding->shares(), $now, $multiplier);
            $shareValue += $valuation;

            $holdingRows[] = [
                'postId'    => $post->id,
                'threadId'  => $post->threadId,
                'excerpt'   => $this->excerpt($post->content),
                'shares'    => $holding->shares(),
                'price'     => round($post->spotPrice(), 2),
                'valuation' => $valuation,
                'cost'      => $holding->cost(),
                'pnl'       => $valuation - $holding->cost(),
                'level'     => $post->level(),
                'status'    => $post->status(),
                'postHp'    => $post->currentHp($now, $multiplier),
            ];
        }

        return [
            'money'      => $user->money(),
            'shareValue' => $shareValue,
            'total'      => $user->money() + $shareValue,
            'holdings'   => $holdingRows,
        ];
    }

    private function excerpt(string $content): string
    {
        $content = trim(preg_replace('/\s+/', ' ', $content) ?? $content);
        if (mb_strlen($content) <= self::EXCERPT_LEN) {
            return $content;
        }
        return mb_substr($content, 0, self::EXCERPT_LEN) . '…';
    }
}
