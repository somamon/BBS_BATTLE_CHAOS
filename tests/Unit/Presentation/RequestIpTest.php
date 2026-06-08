<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation;

use App\Presentation\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestIpTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('TRUSTED_PROXIES'); // クリア
    }

    private function request(array $server): Request
    {
        return new Request('GET', '/', [], [], $server, []);
    }

    public function testReturnsRemoteAddrWhenNoTrustedProxy(): void
    {
        putenv('TRUSTED_PROXIES'); // 未設定
        $r = $this->request(['REMOTE_ADDR' => '198.51.100.9', 'HTTP_X_FORWARDED_FOR' => '203.0.113.5']);
        self::assertSame('198.51.100.9', $r->ip()); // XFFは無視（詐称防止）
    }

    public function testUsesXffWhenRemoteIsTrusted(): void
    {
        putenv('TRUSTED_PROXIES=10.0.0.0/8');
        $r = $this->request(['REMOTE_ADDR' => '10.1.2.3', 'HTTP_X_FORWARDED_FOR' => '203.0.113.5']);
        self::assertSame('203.0.113.5', $r->ip());
    }

    public function testIgnoresXffWhenRemoteUntrusted(): void
    {
        putenv('TRUSTED_PROXIES=10.0.0.0/8');
        $r = $this->request(['REMOTE_ADDR' => '198.51.100.9', 'HTTP_X_FORWARDED_FOR' => '203.0.113.5']);
        self::assertSame('198.51.100.9', $r->ip()); // 信頼外なのでXFFを採用しない
    }

    public function testPicksRightmostNonTrustedInChain(): void
    {
        putenv('TRUSTED_PROXIES=10.0.0.0/8');
        $r = $this->request(['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.5, 10.0.0.2']);
        self::assertSame('203.0.113.5', $r->ip());
    }
}
