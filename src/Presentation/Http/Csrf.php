<?php

declare(strict_types=1);

namespace App\Presentation\Http;

/**
 * セッションに CSRF トークンを保持する簡易ヘルパ。
 * CsrfMiddleware が POST 時に $_SESSION['_csrf'] と入力 _csrf を突き合わせる。
 */
final class Csrf
{
    /** 現在のトークンを返す（無ければ生成してセッションに保存）。 */
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /** フォームに直接埋め込める hidden input を返す。 */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }
}
