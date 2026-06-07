<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Thread;

interface ThreadRepository
{
    /** alive のスレッドを新着順で返す（全言語。終局判定・NPC用）。 */
    /** @return Thread[] */
    public function findAlive(int $limit = 50): array;

    /** dead のスレッドを朽ちた順（新しい順）で返す。墓場一覧用（全言語）。 */
    /** @return Thread[] */
    public function findDead(int $limit = 100): array;

    /** 指定言語の alive スレッドを新着順で返す（一覧表示用。ページング対応）。 */
    /** @return Thread[] */
    public function findAliveByLang(string $lang, int $limit = 50, int $offset = 0): array;

    /** 指定言語の alive スレッド数（ページ総数の算出用。lazy減衰のため概算）。 */
    public function countAliveByLang(string $lang): int;

    /** 指定言語の dead スレッドを朽ちた順で返す（墓場表示用）。 */
    /** @return Thread[] */
    public function findDeadByLang(string $lang, int $limit = 100): array;

    public function findById(string $id): ?Thread;

    /** 行ロック付きで取得（投資トランザクション用）。 */
    public function findByIdForUpdate(string $id): ?Thread;

    public function insert(Thread $thread): void;

    public function save(Thread $thread): void;
}
