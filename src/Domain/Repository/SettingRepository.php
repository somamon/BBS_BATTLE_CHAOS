<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface SettingRepository
{
    /** @return array<string,string> 全設定（キー→値）。 */
    public function all(): array;

    public function get(string $key, ?string $default = null): ?string;

    public function set(string $key, string $value): void;

    public function delete(string $key): void;
}
