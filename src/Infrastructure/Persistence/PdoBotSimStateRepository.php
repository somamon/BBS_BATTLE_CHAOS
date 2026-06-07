<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repository\BotSimStateRepository;
use DateTimeImmutable;
use PDO;

final class PdoBotSimStateRepository implements BotSimStateRepository
{
    public function __construct(private PDO $pdo) {}

    public function getLastTick(): DateTimeImmutable
    {
        $stmt = $this->pdo->query('SELECT last_tick_at FROM bot_sim_state WHERE id = 1');
        $value = $stmt->fetchColumn();

        // 行が無い場合は「今」を返す（初回は経過0でアクションなし）。
        return $value !== false ? new DateTimeImmutable((string) $value) : new DateTimeImmutable();
    }

    public function setLastTick(DateTimeImmutable $at): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO bot_sim_state (id, last_tick_at) VALUES (1, :t)
             ON DUPLICATE KEY UPDATE last_tick_at = VALUES(last_tick_at)'
        );
        $stmt->execute([':t' => $at->format('Y-m-d H:i:s')]);
    }
}
