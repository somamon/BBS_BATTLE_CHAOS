<?php

declare(strict_types=1);

namespace App\Application\UseCase\Admin;

use App\Application\Port\AuditLogger;
use App\Domain\Repository\UserRepository;

/**
 * ユーザーのロール変更（管理者の昇格／降格）。CLI から使う。操作は監査ログに残す。
 */
final class ChangeUserRole
{
    private const ALLOWED = ['user', 'admin'];

    public function __construct(
        private readonly UserRepository $users,
        private readonly AuditLogger $audit,
    ) {}

    /** @return bool 対象が見つかり処理したら true */
    public function execute(string $adminId, string $targetUserId, string $role, ?string $ip = null): bool
    {
        if (!in_array($role, self::ALLOWED, true)) {
            throw new \InvalidArgumentException("invalid role: {$role}");
        }
        $user = $this->users->findById($targetUserId);
        if ($user === null || $user->isBot) {
            return false;
        }
        $user->setRole($role);
        $this->users->save($user);
        $this->audit->record($adminId, 'user.role', 'user', $targetUserId, 'role=' . $role, $ip);
        return true;
    }
}
