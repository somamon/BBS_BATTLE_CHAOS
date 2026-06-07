<?php

declare(strict_types=1);

namespace App\Application\UseCase\Thread;

use App\Application\Service\DecayRate;
use App\Domain\Repository\ThreadRepository;
use DateTimeImmutable;

/**
 * スレッド一覧（ページング）。生存スレッドを1ページ分取得し、現在HPを計算して返す。
 * 現在HPが0以下のものは遅延減衰を確定（dead化）して一覧から除外する。
 *
 * 総ページ数は countAliveByLang を基にした概算（lazy減衰のため、期限切れ未確定分が
 * 一時的に件数へ含まれ得る。表示時に dead 化されるので自己修復する）。
 */
final class ListThreads
{
    /** 1ページの表示件数。 */
    public const PER_PAGE = 20;

    public function __construct(
        private readonly DecayRate $decay,
        private readonly ThreadRepository $threads,
    ) {}

    /**
     * @return array{items:array<int,array<string,mixed>>,page:int,perPage:int,total:int,totalPages:int}
     */
    public function execute(string $lang = 'ja', int $page = 1, int $perPage = self::PER_PAGE, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $multiplier = $this->decay->multiplier($now);
        $perPage = max(1, $perPage);

        $total      = $this->threads->countAliveByLang($lang);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page       = max(1, min($page, $totalPages));
        $offset     = ($page - 1) * $perPage;

        $items = [];
        foreach ($this->threads->findAliveByLang($lang, $perPage, $offset) as $thread) {
            $hp = $thread->currentHp($now, $multiplier);
            if ($hp <= 0) {
                $thread->settleDecay($now, $multiplier); // dead化を確定
                $this->threads->save($thread);
                continue;
            }

            $items[] = [
                'id'        => $thread->id,
                'title'     => $thread->title,
                'hp'        => $hp,
                'maxHp'     => $thread->maxHp(),
                'postCount' => $thread->postCount(),
                'createdAt' => $thread->createdAt->format('Y-m-d H:i'),
            ];
        }

        return [
            'items'      => $items,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
        ];
    }
}
