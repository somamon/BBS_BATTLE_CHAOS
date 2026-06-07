<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\EmailVerification;

interface EmailVerificationRepository
{
    public function insert(EmailVerification $verification): void;

    public function findByTokenHash(string $tokenHash): ?EmailVerification;

    /** 指定ユーザーの未使用トークンをすべて削除（再発行・確認完了時）。 */
    public function deleteForUser(string $userId): void;
}
