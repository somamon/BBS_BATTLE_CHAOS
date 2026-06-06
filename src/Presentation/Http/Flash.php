<?php

declare(strict_types=1);

namespace App\Presentation\Http;

/**
 * 1リクエスト限りのフラッシュメッセージ（リダイレクト後の結果表示用）。
 */
final class Flash
{
    public static function set(string $message): void
    {
        $_SESSION['_flash'] = $message;
    }

    /** メッセージを取り出して即削除（無ければ null）。 */
    public static function pull(): ?string
    {
        $msg = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_flash']);
        return is_string($msg) ? $msg : null;
    }
}
