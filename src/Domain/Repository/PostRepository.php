<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Post;

interface PostRepository
{
    /** 指定スレッドの alive レスを古い順で返す。 */
    /** @return Post[] */
    public function findAliveByThread(string $threadId): array;

    /** 指定スレッドの全レス（dead 含む）を古い順で返す。過去ログ閲覧用。 */
    /** @return Post[] */
    public function findByThread(string $threadId): array;

    /** 指定スレッドの最新レス（二重カキコ判定用）。無ければ null。 */
    public function findLatestByThread(string $threadId): ?Post;

    /** alive レス総数（管理ダッシュボード用）。 */
    public function countAlive(): int;

    /** @return Post[] 新しい順の最近の投稿（非表示も含む。管理モデレーション用。ページング対応）。 */
    public function recentForAdmin(int $limit = 50, int $offset = 0): array;

    /** 全投稿数（管理一覧のページ総数算出用）。 */
    public function countForAdmin(): int;

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
