<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\PasswordReset;

interface PasswordResetRepository
{
    public function insert(PasswordReset $reset): void;

    public function findByTokenHash(string $tokenHash): ?PasswordReset;

    /** 指定ユーザーの未使用トークンをすべて削除（再発行・再設定完了時）。 */
    public function deleteForUser(string $userId): void;

    /** 失効済みトークンを削除する（テーブル肥大の防止）。 */
    public function purgeExpired(\DateTimeImmutable $now): void;
}
