<?php

declare(strict_types=1);

namespace App\Application\UseCase\Admin;

use App\Domain\Repository\InvestmentRepository;
use App\Domain\Repository\PostRepository;
use App\Domain\Repository\RoundRepository;
use App\Domain\Repository\ThreadRepository;
use App\Domain\Repository\UserRepository;

/**
 * 管理ダッシュボードの集計値。重い集計はせず、件数と現ラウンドだけを返す軽量クエリ。
 */
final class AdminStats
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ThreadRepository $threads,
        private readonly PostRepository $posts,
        private readonly InvestmentRepository $investments,
        private readonly RoundRepository $rounds,
    ) {}

    /** @return array{humans:int,aliveThreads:int,alivePosts:int,investments:int,round:?int} */
    public function execute(): array
    {
        return [
            'humans'       => $this->users->countHumans(),
            'aliveThreads' => $this->threads->countAlive(),
            'alivePosts'   => $this->posts->countAlive(),
            'investments'  => $this->investments->count(),
            'round'        => $this->rounds->current()?->id,
        ];
    }
}
