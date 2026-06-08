<?php

declare(strict_types=1);

namespace App\Application\UseCase\Admin;

use App\Application\Port\AuditLogger;
use App\Domain\Entity\Ban;
use App\Domain\Repository\BanRepository;
use App\Domain\Repository\PostRepository;
use DateTimeImmutable;

/**
 * IP BAN（匿名投稿の遮断）。投稿の author_hash（=IPハッシュ）を対象に登録する。
 * ユーザー単位の遮断は凍結（SuspendUser）が担う。操作は監査ログに残す。
 */
final class BanIp
{
    public function __construct(
        private readonly BanRepository $bans,
        private readonly PostRepository $posts,
        private readonly AuditLogger $audit,
    ) {}

    /** 指定投稿の投稿者IP（author_hash）をBANする。 */
    public function banByPost(string $adminId, string $postId, ?string $ip = null): bool
    {
        $post = $this->posts->findById($postId);
        if ($post === null) {
            return false;
        }
        $this->bans->insert(Ban::create('ip', $post->authorHash, 'post:' . $postId, $adminId, new DateTimeImmutable()));
        $this->audit->record($adminId, 'ban.ip', 'ip', $post->authorHash, 'post:' . $postId, $ip);
        return true;
    }

    public function remove(string $adminId, int $banId, ?string $ip = null): void
    {
        $this->bans->removeById($banId);
        $this->audit->record($adminId, 'ban.remove', 'ban', (string) $banId, null, $ip);
    }
}
