<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface AuditLogRepository
{
    /**
     * 監査ログを新しい順に取得する（閲覧用。追記専用テーブルの読み取り）。
     * @return array<int,array{id:int,adminId:string,action:string,targetType:?string,targetId:?string,detail:?string,ip:?string,createdAt:string}>
     */
    public function recent(int $limit = 200): array;
}
