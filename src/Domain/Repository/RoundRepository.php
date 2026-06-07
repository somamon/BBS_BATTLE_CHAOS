<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Round;
use DateTimeImmutable;

/**
 * ラウンドの永続化（M2）。
 *
 * 最終ランキングのスナップショット行は以下の形で受け渡す:
 * array{rank:int,userId:?string,name:string,cash:int,shareValue:int,total:int}
 */
interface RoundRepository
{
    /** 進行中（ended_at IS NULL）の最新ラウンド。無ければ null。 */
    public function current(): ?Round;

    /** 新しいラウンドを開始し、採番済みエンティティを返す。 */
    public function start(DateTimeImmutable $now): Round;

    /** 指定ラウンドを終局として確定する。 */
    public function end(int $roundId, DateTimeImmutable $now, string $reason): void;

    /** 直近の終局済みラウンド（前回結果の表示用）。無ければ null。 */
    public function latestEnded(): ?Round;

    /** @param array<int,array{rank:int,userId:?string,name:string,cash:int,shareValue:int,total:int}> $rows */
    public function saveRankings(int $roundId, array $rows): void;

    /** @return array<int,array<string,mixed>> rank_no 昇順の確定ランキング */
    public function rankings(int $roundId): array;
}
