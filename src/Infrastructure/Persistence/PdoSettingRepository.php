<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repository\SettingRepository;
use DateTimeImmutable;
use PDO;

final class PdoSettingRepository implements SettingRepository
{
    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        $rows = $this->pdo->query('SELECT k, v FROM settings')->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['k']] = $row['v'];
        }
        return $map;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->pdo->prepare('SELECT v FROM settings WHERE k = ?');
        $stmt->execute([$key]);
        $v = $stmt->fetchColumn();
        return $v === false ? $default : (string) $v;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (k, v, updated_at) VALUES (:k, :v, :u)
             ON DUPLICATE KEY UPDATE v = VALUES(v), updated_at = VALUES(updated_at)'
        );
        $stmt->execute([':k' => $key, ':v' => $value, ':u' => (new DateTimeImmutable())->format('Y-m-d H:i:s')]);
    }

    public function delete(string $key): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM settings WHERE k = ?');
        $stmt->execute([$key]);
    }
}
