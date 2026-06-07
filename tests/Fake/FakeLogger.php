<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Application\Port\Logger;

final class FakeLogger implements Logger
{
    /** @var array<int,array{level:string,message:string,context:array<string,mixed>}> */
    public array $records = [];

    public function log(string $level, string $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }

    public function event(string $name, array $context = []): void
    {
        $this->log('event', $name, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /** @return array<int,array{level:string,message:string,context:array<string,mixed>}> */
    public function eventsNamed(string $name): array
    {
        return array_values(array_filter(
            $this->records,
            static fn (array $r): bool => $r['level'] === 'event' && $r['message'] === $name,
        ));
    }
}
