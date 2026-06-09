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

    /**
     * 送出する CSP ヘッダ値。
     *
     * 広告(忍者admax)はページ内で外部+インラインのスクリプトを実行し、
     * 広告クリエイティブを各種ドメインの iframe/画像/ビーコンで配信するため、
     * script/img/frame/connect を https に開く（'unsafe-inline' も許可）。
     * トレードオフ: 厳格な nonce ベースより XSS 耐性は下がるが、ユーザー出力は
     * すべて View::e でエスケープ済みのため実用上の露出は限定的（広告掲載のための妥協）。
     * nonce は自前インラインJSの保険として引き続き付与するが、'unsafe-inline' 下では装飾的。
     */
    public static function policy(): string
    {
        return "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; "
            . "style-src 'self' 'unsafe-inline' https:; "
            . "img-src 'self' data: https:; "
            . "media-src https: data:; "
            . "font-src 'self' data: https:; "
            . "frame-src https:; "
            . "connect-src 'self' https:; "
            . "base-uri 'none'; form-action 'self'; frame-ancestors 'none'";
    }
}
