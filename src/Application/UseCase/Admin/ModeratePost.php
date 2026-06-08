<?php

declare(strict_types=1);

namespace App\Application\UseCase\Admin;

use App\Application\Port\AuditLogger;
use App\Domain\Repository\PostRepository;
use DateTimeImmutable;

/**
 * 投稿（レス）の非表示／復帰（管理モデレーション）。可逆。操作は監査ログに残す。
 */
final class ModeratePost
{
    public function __construct(
        private readonly PostRepository $posts,
        private readonly AuditLogger $audit,
    ) {}

    public function hide(string $adminId, string $postId, ?string $ip = null): bool
    {
        $post = $this->posts->findById($postId);
        if ($post === null) {
            return false;
        }
        $post->hide($adminId, new DateTimeImmutable());
        $this->posts->save($post);
        $this->audit->record($adminId, 'post.hide', 'post', $postId, null, $ip);
        return true;
    }

    public function unhide(string $adminId, string $postId, ?string $ip = null): bool
    {
        $post = $this->posts->findById($postId);
        if ($post === null) {
            return false;
        }
        $post->unhide();
        $this->posts->save($post);
        $this->audit->record($adminId, 'post.unhide', 'post', $postId, null, $ip);
        return true;
    }
}
