<?php

declare(strict_types=1);

namespace App\Application\UseCase\Ranking;

use App\Application\Service\MarketPhaseService;
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
        private readonly MarketPhaseService $market,
        private readonly UserRepository $users,
        private readonly HoldingRepository $holdings,
        private readonly PostRepository $posts,
    ) {}

    /** @return array<int,array<string,mixed>> 総額降順のランキング */
    public function execute(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $multiplier = $this->market->resolve($now)->multiplier();

        $rows = [];
        foreach ($this->users->all() as $user) {
            $shareValue = 0;
            foreach ($this->holdings->findByUser($user->id) as $holding) {
                $post = $this->posts->findById($holding->postId);
                if ($post === null) {
                    continue;
                }
                $shareValue += $post->valuation($holding->shares(), $now, $multiplier);
            }

            $rows[] = [
                'name'       => $user->name,
                'money'      => $user->money(),
                'shareValue' => $shareValue,
                'total'      => $user->money() + $shareValue,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return $rows;
    }
}
