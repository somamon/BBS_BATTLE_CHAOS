<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Post;

interface PostRepository
{
    /** 指定スレッドの alive レスを古い順で返す。 */
    /** @return Post[] */
    public function findAliveByThread(string $threadId): array;

    public function findById(string $id): ?Post;

    /** 行ロック付きで取得（投資トランザクション用）。 */
    public function findByIdForUpdate(string $id): ?Post;

    public function insert(Post $post): void;

    /** 株・HP・レベル等の状態を保存（投資・減衰確定時）。 */
    public function save(Post $post): void;
}
