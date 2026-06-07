<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation;

use App\Presentation\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testHtmlIncludesSecurityHeaders(): void
    {
        $headers = Response::html('<p>hi</p>')->headers();

        self::assertSame('DENY', $headers['X-Frame-Options']);
        self::assertSame('nosniff', $headers['X-Content-Type-Options']);
        self::assertSame('no-referrer', $headers['Referrer-Policy']);
        self::assertArrayHasKey('Content-Security-Policy', $headers);
        self::assertStringContainsString("frame-ancestors 'none'", $headers['Content-Security-Policy']);
        self::assertSame('text/html; charset=UTF-8', $headers['Content-Type']);
    }

    public function testRedirectAlsoCarriesSecurityHeaders(): void
    {
        $headers = Response::redirect('/threads')->headers();

        self::assertSame('/threads', $headers['Location']);
        self::assertSame('DENY', $headers['X-Frame-Options']);
    }

    public function testExplicitHeaderOverridesDefault(): void
    {
        $headers = Response::html('x')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->headers();

        self::assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
    }
}
