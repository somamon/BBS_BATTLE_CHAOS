<?php

declare(strict_types=1);

namespace App\Application\UseCase\Ranking;

use App\Application\Service\MarketPhaseService;
use App\Domain\Repository\HoldingRepository;
use App\Domain\Repository\ThreadRepository;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * 総資産ランキング。所持金＋保有株の時価評価額で全ユーザーを総額降順に並べる。
 * 株時価 = floor(保有株数 / totalShares × スレッド現在HP)。
 */
final class RankingQuery
{
    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly UserRepository $users,
        private readonly HoldingRepository $holdings,
        private readonly ThreadRepository $threads,
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
                $thread = $this->threads->findById($holding->threadId);
                if ($thread === null) {
                    continue;
                }
                $totalShares = $thread->totalShares();
                if ($totalShares > 0) {
                    $shareValue += (int) floor(
                        $holding->shares() / $totalShares * $thread->currentHp($now, $multiplier),
                    );
                }
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
