<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Port\GameStateResetter;
use PDO;

/**
 * 遊技データの初期化（M2）。FK 制約に従い削除順を守る。
 *
 * 注意: TRUNCATE は MySQL で暗黙コミットを起こすため使わない（呼び出し側のトランザクションを壊す）。
 * すべて DELETE と UPDATE で行い、原子性を保つ。
 */
final class PdoGameStateResetter implements GameStateResetter
{
    public function __construct(private PDO $pdo) {}

    public function reset(int $humanMoney): void
    {
        // FK: investments→(users RESTRICT, posts RESTRICT) を先に消す。次に holdings、posts、threads。
        $this->pdo->exec('DELETE FROM investments');
        $this->pdo->exec('DELETE FROM holdings');
        $this->pdo->exec('DELETE FROM posts');
        $this->pdo->exec('DELETE FROM threads');

        // 人間ユーザーの所持金を初期値へ。アカウントは残す。
        $stmt = $this->pdo->prepare('UPDATE users SET money = ? WHERE is_bot = 0');
        $stmt->execute([$humanMoney]);

        // 相場フェーズとボットtickの状態を初期化。
        $this->pdo->exec(
            "UPDATE world_state
                SET phase = 'calm', phase_multiplier = 1.0, next_shift_at = NOW(), updated_at = NOW()
              WHERE id = 1"
        );
        $this->pdo->exec('UPDATE bot_sim_state SET last_tick_at = NOW() WHERE id = 1');
    }
}
