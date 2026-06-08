<?php

declare(strict_types=1);

namespace App\Application\UseCase\Admin;

use App\Application\Port\AuditLogger;
use App\Domain\Repository\ThreadRepository;
use DateTimeImmutable;

/**
 * スレッドの非表示／復帰（管理モデレーション）。可逆。操作は監査ログに残す。
 * スレを非表示にすると公開側からは一覧・詳細とも見えなくなる（配下レスも到達不能）。
 */
final class ModerateThread
{
    public function __construct(
        private readonly ThreadRepository $threads,
        private readonly AuditLogger $audit,
    ) {}

    public function hide(string $adminId, string $threadId, ?string $ip = null): bool
    {
        $thread = $this->threads->findById($threadId);
        if ($thread === null) {
            return false;
        }
        $thread->hide($adminId, new DateTimeImmutable());
        $this->threads->save($thread);
        $this->audit->record($adminId, 'thread.hide', 'thread', $threadId, null, $ip);
        return true;
    }

    public function unhide(string $adminId, string $threadId, ?string $ip = null): bool
    {
        $thread = $this->threads->findById($threadId);
        if ($thread === null) {
            return false;
        }
        $thread->unhide();
        $this->threads->save($thread);
        $this->audit->record($adminId, 'thread.unhide', 'thread', $threadId, null, $ip);
        return true;
    }
}
