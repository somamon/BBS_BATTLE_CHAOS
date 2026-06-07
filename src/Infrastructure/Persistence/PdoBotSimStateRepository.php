<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repository\BotSimStateRepository;
use DateTimeImmutable;
use PDO;

final class PdoBotSimStateRepository implements BotSimStateRepository
{
    public function __construct(private PDO $pdo) {}

    public function tryClaim(DateTimeImmutable $now, int $minIntervalSeconds): ?DateTimeImmutable
    {
        $stmt = $this->pdo->query('SELECT last_tick_at FROM bot_sim_state WHERE id = 1');
        $value = $stmt->fetchColumn();
        if ($value === false) {
            return null; // 行が無い（マイグレーション未適用）
        }
        $prev = new DateTimeImmutable((string) $value);

        if ($now->getTimestamp() - $prev->getTimestamp() < $minIntervalSeconds) {
            return null; // まだ間隔未満
        }

        // 楽観的ロック：last_tick が読んだ値のままなら now へ進める。負ければ他が占有済み。
        $upd = $this->pdo->prepare(
            'UPDATE bot_sim_state SET last_tick_at = :now WHERE id = 1 AND last_tick_at = :prev'
        );
        $upd->execute([
            ':now'  => $now->format('Y-m-d H:i:s'),
            ':prev' => $prev->format('Y-m-d H:i:s'),
        ]);

        return $upd->rowCount() === 1 ? $prev : null;
    }
}
