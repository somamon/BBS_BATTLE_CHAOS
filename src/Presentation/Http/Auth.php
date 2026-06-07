<?php

declare(strict_types=1);

namespace App\Presentation\Http;

/**
 * セッションベースの認証状態。$_SESSION['uid'] にログインユーザーIDを保持する。
 * PHP-DI の autowiring で生成できるようコンストラクタ引数は持たない。
 */
final class Auth
{
    /** ログイン状態にする。セッション固定攻撃を防ぐため ID を再生成する。 */
    public function login(string $userId): void
    {
        // CLI（テスト）では active セッションが無いので再生成はスキップ。
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['uid'] = $userId;
    }

    /** ログアウト（セッションからIDを除去し、IDも作り直す）。 */
    public function logout(): void
    {
        unset($_SESSION['uid']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
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
