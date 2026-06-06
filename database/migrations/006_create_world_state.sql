-- 世界フェーズ（相場天候・常に1行）
CREATE TABLE IF NOT EXISTS world_state (
    id               TINYINT      NOT NULL DEFAULT 1,
    phase            VARCHAR(10)  NOT NULL DEFAULT 'calm',   -- boom / calm / storm / crash
    phase_multiplier DECIMAL(3,1) NOT NULL DEFAULT 1.0,
    next_shift_at    DATETIME     NOT NULL,
    updated_at       DATETIME     NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 初期1行（平穏・倍率1.0・即時に次回抽選可）
INSERT INTO world_state (id, phase, phase_multiplier, next_shift_at, updated_at)
VALUES (1, 'calm', 1.0, NOW(), NOW())
ON DUPLICATE KEY UPDATE id = id;
