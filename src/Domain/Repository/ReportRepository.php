<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Report;

interface ReportRepository
{
    public function insert(Report $report): void;

    /** @return Report[] 未対応(open)の通報を新しい順に返す。 */
    public function listOpen(int $limit = 100): array;

    /** 対応状態を更新する（resolved / rejected）。 */
    public function setStatus(string $id, string $status): void;

    /** 未対応件数（バッジ表示用）。 */
    public function countOpen(): int;
}
