<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\WorldState;
use App\Domain\Repository\WorldStateRepository;
use DateTimeImmutable;
use PDO;
use RuntimeException;

final class PdoWorldStateRepository implements WorldStateRepository
{
    public function __construct(private PDO $pdo) {}

    public function get(): WorldState
    {
        $stmt = $this->pdo->query('SELECT * FROM world_state WHERE id = 1');
        $row = $stmt->fetch();

        if ($row === false) {
            throw new RuntimeException('world_state row (id=1) not found');
        }

        return new WorldState(
            phase:           $row['phase'],
            phaseMultiplier: (float) $row['phase_multiplier'],
            nextShiftAt:     new DateTimeImmutable($row['next_shift_at']),
            updatedAt:       new DateTimeImmutable($row['updated_at']),
        );
    }

    public function save(WorldState $state): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE world_state SET
                phase = :phase,
                phase_multiplier = :phase_multiplier,
                next_shift_at = :next_shift_at,
                updated_at = :updated_at
             WHERE id = 1'
        );
        $stmt->execute([
            ':phase'            => $state->phase(),
            ':phase_multiplier' => $state->multiplier(),
            ':next_shift_at'    => $state->nextShiftAt()->format('Y-m-d H:i:s'),
            ':updated_at'       => $state->updatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
}
