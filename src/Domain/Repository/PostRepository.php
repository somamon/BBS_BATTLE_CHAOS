<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Post;

interface PostRepository
{
    /** 指定スレッドの alive レスを古い順で返す。 */
    /** @return Post[] */
    public function findAliveByThread(string $threadId): array;

    /** alive な投稿を横断的に返す（NPC投資の対象選択用）。 */
    /** @return Post[] */
    public function findAlive(int $limit = 100): array;

    public function findById(string $id): ?Post;

    /** @param string[] $ids @return array<string,Post> id をキーにした連想配列（N+1回避の一括取得）。 */
    public function findByIds(array $ids): array;

    /** 行ロック付きで取得（投資トランザクション用）。 */
    public function findByIdForUpdate(string $id): ?Post;

    public function insert(Post $post): void;

    /** 株・HP・レベル等の状態を保存（投資・減衰確定時）。 */
    public function save(Post $post): void;
}
