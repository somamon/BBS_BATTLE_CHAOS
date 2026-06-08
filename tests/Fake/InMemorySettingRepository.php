<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Repository\SettingRepository;

final class InMemorySettingRepository implements SettingRepository
{
    /** @var array<string,string> */
    public array $map = [];

    public function all(): array
    {
        return $this->map;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->map[$key] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $this->map[$key] = $value;
    }

    public function delete(string $key): void
    {
        unset($this->map[$key]);
    }
}
