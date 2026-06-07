<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Logging\JsonLogger;
use App\Infrastructure\Logging\RequestContext;
use PHPUnit\Framework\TestCase;

final class JsonLoggerTest extends TestCase
{
    /** error_log の出力を一時ファイルへ向けて検証する。 */
    private function captureLog(callable $fn): string
    {
        $file = tempnam(sys_get_temp_dir(), 'log');
        $prevDest = ini_get('error_log');
        ini_set('error_log', $file);
        // error_log() は第1引数のみだと ini の error_log 先（=今設定したファイル）へ出力される。
        try {
            $fn();
        } finally {
            ini_set('error_log', $prevDest === false ? '' : (string) $prevDest);
        }
        return (string) file_get_contents($file);
    }

    public function testEmitsSingleJsonLineWithRequestIdAndContext(): void
    {
        RequestContext::init('test-corr-id');
        $logger = new JsonLogger('testing');

        $out = $this->captureLog(function () use ($logger): void {
            $logger->event('user_registered', ['user_id' => 'u1']);
        });

        // 末尾の1行を取り出す（error_log はタイムスタンプ接頭辞を付ける場合がある）。
        $line = trim($out);
        $jsonStart = strpos($line, '{');
        self::assertNotFalse($jsonStart);
        $decoded = json_decode(substr($line, $jsonStart), true);

        self::assertIsArray($decoded);
        self::assertSame('event', $decoded['level']);
        self::assertSame('user_registered', $decoded['msg']);
        self::assertSame('test-corr-id', $decoded['request_id']);
        self::assertSame('testing', $decoded['env']);
        self::assertSame('u1', $decoded['ctx']['user_id']);
    }
}
