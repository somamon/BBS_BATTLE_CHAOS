<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Application\Port\AuditLogger;

final class FakeAuditLogger implements AuditLogger
{
    /** @var array<int,array{adminId:string,action:string,targetType:?string,targetId:?string,detail:?string,ip:?string}> */
    public array $records = [];

    public function record(
        string $adminId,
        string $action,
        ?string $targetType = null,
        ?string $targetId = null,
        ?string $detail = null,
        ?string $ip = null,
    ): void {
        $this->records[] = compact('adminId', 'action', 'targetType', 'targetId', 'detail', 'ip');
    }

    /** @return array<int,array<string,mixed>> 指定アクションの記録のみ */
    public function actions(string $action): array
    {
        return array_values(array_filter($this->records, static fn (array $r): bool => $r['action'] === $action));
    }
}
