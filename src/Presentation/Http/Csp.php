<?php

declare(strict_types=1);

namespace App\Presentation\Http;

/**
 * Content-Security-Policy のリクエスト単位 nonce とポリシー文字列を提供する。
 *
 * インラインスクリプトは原則禁止（script-src 'self'）だが、レイアウトのカウントダウン等
 * 限定的なインラインJSのために nonce を1リクエスト1値で発行する。レイアウト描画時に
 * {@see nonce()} を <script nonce> へ、送出時に {@see policy()} を CSP ヘッダへ載せると同値が一致する。
 */
final class Csp
{
    private static ?string $nonce = null;

    /** このリクエストの nonce（初回呼び出しで生成し以後同値）。 */
    public static function nonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = base64_encode(random_bytes(16));
        }
        return self::$nonce;
    }

    /** 送出する CSP ヘッダ値。script は self ＋ 当該リクエストの nonce のみ許可。 */
    public static function policy(): string
    {
        $nonce = self::nonce();
        return "default-src 'self'; script-src 'self' 'nonce-{$nonce}'; "
            . "style-src 'self' 'unsafe-inline'; img-src 'self' data:; "
            . "base-uri 'none'; form-action 'self'; frame-ancestors 'none'";
    }
}
