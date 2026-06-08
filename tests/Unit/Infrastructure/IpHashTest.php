<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Security\IpHash;
use PHPUnit\Framework\TestCase;

final class IpHashTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('APP_SECRET');
    }

    public function testDeterministicForSameSecret(): void
    {
        putenv('APP_SECRET=test-secret-123456');
        self::assertSame(IpHash::of('203.0.113.5'), IpHash::of('203.0.113.5'));
    }

    public function testNotPlainSha256(): void
    {
        putenv('APP_SECRET=test-secret-123456');
        // 素の sha256 とは異なる（HMACで鍵付き＝逆引き困難）。
        self::assertNotSame(hash('sha256', '203.0.113.5'), IpHash::of('203.0.113.5'));
    }

    public function testChangesWithSecret(): void
    {
        putenv('APP_SECRET=secret-A-xxxxxxxx');
        $a = IpHash::of('203.0.113.5');
        putenv('APP_SECRET=secret-B-yyyyyyyy');
        $b = IpHash::of('203.0.113.5');
        self::assertNotSame($a, $b);
    }
}
