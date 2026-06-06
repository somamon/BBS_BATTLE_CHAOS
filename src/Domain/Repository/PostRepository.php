<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Post;

interface PostRepository
{
    /** 指定スレッドの alive レスを古い順で返す。 */
    /** @return Post[] */
    public function findAliveByThread(string $threadId): array;

    public function insert(Post $post): void;
}
