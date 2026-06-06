<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Service\MarketPhaseService;
use App\Domain\Repository\HoldingRepository;
use App\Domain\Repository\ThreadRepository;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * マイページ。ユーザーの所持金と保有株の時価評価を返す。
 * 株評価 = floor(保有株数 / totalShares × スレッド現在HP)。
 */
final class MyPageQuery
{
    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly UserRepository $users,
        private readonly HoldingRepository $holdings,
        private readonly ThreadRepository $threads,
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
            $thread = $this->threads->findById($holding->threadId);
            if ($thread === null) {
                continue;
            }
            $threadHp = $thread->currentHp($now, $multiplier);
            $totalShares = $thread->totalShares();
            $valuation = 0;
            if ($totalShares > 0) {
                $valuation = (int) floor($holding->shares() / $totalShares * $threadHp);
            }
            $shareValue += $valuation;

            $holdingRows[] = [
                'threadId'    => $thread->id,
                'threadTitle' => $thread->title,
                'shares'      => $holding->shares(),
                'valuation'   => $valuation,
                'status'      => $thread->status(),
                'threadHp'    => $threadHp,
            ];
        }

        return [
            'money'      => $user->money(),
            'shareValue' => $shareValue,
            'total'      => $user->money() + $shareValue,
            'holdings'   => $holdingRows,
        ];
    }
}
