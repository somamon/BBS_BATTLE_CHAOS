<?php

declare(strict_types=1);

namespace App\Application\UseCase\Ranking;

use App\Application\Service\DecayRate;
use App\Domain\Repository\HoldingRepository;
use App\Domain\Repository\PostRepository;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * 総資産ランキング。所持金＋保有株の時価評価額で全ユーザーを総額降順に並べる。
 * 株評価 = 保有株数 × スポット株価 × 鮮度（マークトゥマーケット。doc21 §2.4）。
 */
final class RankingQuery
{
    public function __construct(
        private readonly DecayRate $decay,
        private readonly UserRepository $users,
        private readonly HoldingRepository $holdings,
        private readonly PostRepository $posts,
    ) {}

    /** @return array<int,array<string,mixed>> 総額降順のランキング */
    public function execute(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $multiplier = $this->decay->multiplier($now);

        // 一括取得して N+1 を回避：全保有株 → ユーザー別にまとめ、関連投稿を IN で1回取得。
        $holdingsByUser = [];
        $postIds = [];
        foreach ($this->holdings->all() as $holding) {
            $holdingsByUser[$holding->userId][] = $holding;
            $postIds[] = $holding->postId;
        }
        $postMap = $this->posts->findByIds($postIds);

        $rows = [];
        foreach ($this->users->all() as $user) {
            // NPC（is_bot=1）はランキング対象外。順位は人間プレイヤー同士で競う。
            if ($user->isBot) {
                continue;
            }

            $shareValue = 0;
            foreach ($holdingsByUser[$user->id] ?? [] as $holding) {
                $post = $postMap[$holding->postId] ?? null;
                if ($post === null) {
                    continue;
                }
                $shareValue += $post->valuation($holding->shares(), $now, $multiplier);
            }

            $rows[] = [
                'name'       => $user->name,
                'isBot'      => $user->isBot,
                'money'      => $user->money(),
                'shareValue' => $shareValue,
                'total'      => $user->money() + $shareValue,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return $rows;
    }
}
