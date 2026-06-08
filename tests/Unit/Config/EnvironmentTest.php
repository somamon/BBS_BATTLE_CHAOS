<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Config\Environment;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    /** @var array<string,string|false> */
    private array $saved = [];

    private function setEnv(array $vars): void
    {
        foreach ($vars as $k => $v) {
            $this->saved[$k] ??= getenv($k);
            putenv("{$k}={$v}");
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->saved as $k => $v) {
            $v === false ? putenv($k) : putenv("{$k}={$v}");
        }
        $this->saved = [];
    }

    public function testNonProductionSkipsChecks(): void
    {
        $this->setEnv(['APP_ENV' => 'development', 'DB_PASSWORD' => 'bbs', 'APP_URL' => 'http://x', 'MAIL_DRIVER' => 'log']);
        Environment::assertProductionReady();
        $this->expectNotToPerformAssertions();
    }

    public function testProductionRejectsWeakConfig(): void
    {
        $this->setEnv(['APP_ENV' => 'production', 'DB_PASSWORD' => 'root', 'APP_URL' => 'http://x', 'MAIL_DRIVER' => 'log']);
        $this->expectException(\RuntimeException::class);
        Environment::assertProductionReady();
    }

    public function testProductionAcceptsHardenedConfig(): void
    {
        $this->setEnv([
            'APP_ENV'     => 'production',
            'DB_PASSWORD' => 'a-strong-secret-123',
            'APP_URL'     => 'https://bbschaos.example',
            'MAIL_DRIVER' => 'smtp',
            'APP_SECRET'  => 'a-strong-app-secret-key',
        ]);
        Environment::assertProductionReady();
        $this->expectNotToPerformAssertions();
    }
}
