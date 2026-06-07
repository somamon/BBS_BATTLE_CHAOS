<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Auth\GoogleOAuth;
use PHPUnit\Framework\TestCase;

final class GoogleOAuthTest extends TestCase
{
    public function testNotConfiguredWhenCredentialsMissing(): void
    {
        $oauth = new GoogleOAuth('', '', 'https://app.example/auth/google/callback');
        self::assertFalse($oauth->isConfigured());
    }

    public function testConfiguredWhenAllPresent(): void
    {
        $oauth = new GoogleOAuth('cid', 'secret', 'https://app.example/auth/google/callback');
        self::assertTrue($oauth->isConfigured());
    }

    public function testAuthUrlContainsRequiredParams(): void
    {
        $oauth = new GoogleOAuth('my-client-id', 'secret', 'https://app.example/auth/google/callback');
        $verifier = $oauth->generateCodeVerifier();
        $url = $oauth->authUrl('the-state', $verifier);

        self::assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        self::assertSame('my-client-id', $q['client_id']);
        self::assertSame('https://app.example/auth/google/callback', $q['redirect_uri']);
        self::assertSame('code', $q['response_type']);
        self::assertSame('the-state', $q['state']);
        self::assertSame('S256', $q['code_challenge_method']);
        self::assertArrayHasKey('code_challenge', $q);
        self::assertStringContainsString('openid', $q['scope']);
        // PKCE: challenge は verifier の SHA-256 を URLセーフ Base64（パディング無し）したもの。
        $expected = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        self::assertSame($expected, $q['code_challenge']);
    }

    public function testCodeVerifierIsUrlSafe(): void
    {
        $oauth = new GoogleOAuth('cid', 'secret', 'https://app.example/cb');
        $v = $oauth->generateCodeVerifier();
        self::assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $v);
        self::assertGreaterThanOrEqual(43, strlen($v)); // RFC 7636 の下限
    }
}
