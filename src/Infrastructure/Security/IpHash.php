<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

/**
 * IPアドレスの一方向ハッシュ。匿名識別（author_hash）・IP BAN のキーに使う。
 *
 * 素の sha256(ip) は IPv4 空間が狭く総当たりで逆引きできてしまい匿名化にならない。
 * そこでアプリ秘密鍵（APP_SECRET）付きの HMAC を使い、DB が漏れても元IPを復元できないようにする。
 *
 * 注意: APP_SECRET を変更すると過去の author_hash / IP BAN と一致しなくなる（実質リセット）。
 */
final class IpHash
{
    /** 開発用フォールバック（本番は APP_SECRET を必須にする。Environment で検証）。 */
    private const DEV_FALLBACK_SECRET = 'dev-insecure-ip-hash-secret';

    public static function of(string $ip): string
    {
        $secret = getenv('APP_SECRET') ?: self::DEV_FALLBACK_SECRET;
        return hash_hmac('sha256', $ip, $secret);
    }
}
