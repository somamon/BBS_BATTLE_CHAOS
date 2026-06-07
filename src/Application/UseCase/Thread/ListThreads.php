<?php

declare(strict_types=1);

namespace App\Application\UseCase\Thread;

use App\Application\Service\MarketPhaseService;
use App\Domain\Repository\ThreadRepository;
use DateTimeImmutable;

/**
 * スレッド一覧。生存スレッドを取得し、現在HPを計算して返す。
 * 現在HPが0以下のものは遅延減衰を確定（dead化）して一覧から除外する。
 */
final class ListThreads
{
    public function __construct(
        private readonly MarketPhaseService $market,
        private readonly ThreadRepository $threads,
    ) {}

    /** @return array<int,array<string,mixed>> 生存スレッドの表示用データ */
    public function execute(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $multiplier = $this->market->resolve($now)->multiplier();

        $result = [];
        foreach ($this->threads->findAlive(50) as $thread) {
            $hp = $thread->currentHp($now, $multiplier);
            if ($hp <= 0) {
                $thread->settleDecay($now, $multiplier); // dead化を確定
                $this->threads->save($thread);
                continue;
            }

            $result[] = [
                'id'        => $thread->id,
                'title'     => $thread->title,
                'hp'        => $hp,
                'maxHp'     => $thread->maxHp(),
                'postCount' => $thread->postCount(),
                'createdAt' => $thread->createdAt->format('Y-m-d H:i'),
            ];
        }

        return $result;
    }
}
