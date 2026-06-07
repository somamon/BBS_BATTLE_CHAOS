<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Service\MarketPhaseService;
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

    private const LEVEL_LABELS = ['新規', '注目', '人気', '殿堂入り'];

    public function __construct(
        private readonly MarketPhaseService $market,
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

        $multiplier = $this->market->resolve($now)->multiplier();

        $shareValue = 0;
        $holdingRows = [];
        foreach ($this->holdings->findByUser($userId) as $holding) {
            if ($holding->shares() <= 0) {
                continue;
            }
            $post = $this->posts->findById($holding->postId);
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
                'level'     => self::LEVEL_LABELS[$post->level()] ?? '新規',
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
