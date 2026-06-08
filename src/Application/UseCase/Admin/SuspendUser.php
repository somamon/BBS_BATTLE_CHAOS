<?php

declare(strict_types=1);

namespace App\Application\UseCase\Admin;

use App\Application\Port\AuditLogger;
use App\Domain\Repository\UserRepository;

/**
 * ユーザーの凍結／解除（管理操作）。NPC(is_bot) は対象外。操作は監査ログに残す。
 */
final class SuspendUser
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuditLogger $audit,
    ) {}

    /** @return bool 対象が見つかり処理したら true */
    public function suspend(string $adminId, string $targetUserId, ?string $ip = null): bool
    {
        $user = $this->users->findById($targetUserId);
        if ($user === null || $user->isBot) {
            return false;
        }
        $user->suspend();
        $this->users->save($user);
        $this->audit->record($adminId, 'user.suspend', 'user', $targetUserId, null, $ip);
        return true;
    }

    /** @return bool 対象が見つかり処理したら true */
    public function unsuspend(string $adminId, string $targetUserId, ?string $ip = null): bool
    {
        $user = $this->users->findById($targetUserId);
        if ($user === null || $user->isBot) {
            return false;
        }
        $user->unsuspend();
        $this->users->save($user);
        $this->audit->record($adminId, 'user.unsuspend', 'user', $targetUserId, null, $ip);
        return true;
    }
}
