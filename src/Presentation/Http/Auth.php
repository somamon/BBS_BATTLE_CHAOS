<?php

declare(strict_types=1);

namespace App\Presentation\Http;

/**
 * セッションベースの認証状態。$_SESSION['uid'] にログインユーザーIDを保持する。
 * PHP-DI の autowiring で生成できるようコンストラクタ引数は持たない。
 */
final class Auth
{
    /** ログイン状態にする。 */
    public function login(string $userId): void
    {
        $_SESSION['uid'] = $userId;
    }

    /** ログアウト（セッションからIDを除去）。 */
    public function logout(): void
    {
        unset($_SESSION['uid']);
    }

    /** ログイン中のユーザーID（未ログインなら null）。 */
    public function userId(): ?string
    {
        $uid = $_SESSION['uid'] ?? null;
        return is_string($uid) && $uid !== '' ? $uid : null;
    }

    /** ログイン済みか。 */
    public function check(): bool
    {
        return $this->userId() !== null;
    }
}
