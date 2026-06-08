<?php

declare(strict_types=1);

namespace App\Application\UseCase\Admin;

use App\Application\Port\AuditLogger;
use App\Domain\Entity\Ban;
use App\Domain\Repository\BanRepository;
use App\Domain\Repository\PostRepository;
use DateTimeImmutable;

/**
 * IP BAN（匿名投稿の遮断）。投稿の author_hash（=IPハッシュ）またはIP直接入力を対象に登録する。
 * 期限（expiresAt）が null なら無期限。ユーザー単位の遮断は SuspendUser / BanUser が担う。
 */
final class BanIp
{
    public function __construct(
        private readonly BanRepository $bans,
        private readonly PostRepository $posts,
        private readonly AuditLogger $audit,
    ) {}

    /** 指定投稿の投稿者IP（author_hash）をBANする。 */
    public function banByPost(string $adminId, string $postId, ?string $opIp = null, ?DateTimeImmutable $expiresAt = null): bool
    {
        $post = $this->posts->findById($postId);
        if ($post === null) {
            return false;
        }
        $this->bans->insert(Ban::create('ip', $post->authorHash, 'post:' . $postId, $adminId, new DateTimeImmutable(), $expiresAt));
        $this->audit->record($adminId, 'ban.ip', 'ip', $post->authorHash, 'post:' . $postId, $opIp);
        return true;
    }

    /** IPアドレスを直接指定してBANする（author_hash と同じ sha256 でハッシュ化）。 */
    public function banAddress(string $adminId, string $ipAddress, ?string $reason, ?string $opIp = null, ?DateTimeImmutable $expiresAt = null): bool
    {
        $ipAddress = trim($ipAddress);
        if ($ipAddress === '') {
            return false;
        }
        $hash = \App\Infrastructure\Security\IpHash::of($ipAddress);
        $this->bans->insert(Ban::create('ip', $hash, $reason, $adminId, new DateTimeImmutable(), $expiresAt));
        $this->audit->record($adminId, 'ban.ip', 'ip', $hash, $reason, $opIp);
        return true;
    }

    public function remove(string $adminId, int $banId, ?string $opIp = null): void
    {
        $this->bans->removeById($banId);
        $this->audit->record($adminId, 'ban.remove', 'ban', (string) $banId, null, $opIp);
    }
}
