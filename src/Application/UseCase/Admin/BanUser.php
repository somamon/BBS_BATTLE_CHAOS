<?php

declare(strict_types=1);

namespace App\Application\UseCase\Admin;

use App\Application\Port\AuditLogger;
use App\Domain\Entity\Ban;
use App\Domain\Repository\BanRepository;
use App\Domain\Repository\UserRepository;
use DateTimeImmutable;

/**
 * ユーザー単位のBAN／解除。bans('user') に記録し、合わせて凍結（status=suspended）する。
 * これによりログイン拒否＋（投資など）アクティブセッションでの操作も塞がる。操作は監査ログに残す。
 */
final class BanUser
{
    public function __construct(
        private readonly BanRepository $bans,
        private readonly UserRepository $users,
        private readonly AuditLogger $audit,
    ) {}

    public function ban(string $adminId, string $userId, ?string $reason = null, ?string $ip = null, ?DateTimeImmutable $expiresAt = null): bool
    {
        $user = $this->users->findById($userId);
        if ($user === null || $user->isBot) {
            return false;
        }
        $this->bans->insert(Ban::create('user', $userId, $reason, $adminId, new DateTimeImmutable(), $expiresAt));
        $user->suspend($expiresAt);
        $this->users->save($user);
        $this->audit->record($adminId, 'user.ban', 'user', $userId, $reason, $ip);
        return true;
    }

    public function unban(string $adminId, string $userId, ?string $ip = null): bool
    {
        $user = $this->users->findById($userId);
        if ($user === null) {
            return false;
        }
        $this->bans->removeByKindValue('user', $userId);
        $user->unsuspend();
        $this->users->save($user);
        $this->audit->record($adminId, 'user.unban', 'user', $userId, null, $ip);
        return true;
    }
}
