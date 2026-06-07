-- NPC投資家シミュレーションの遅延tick用の状態（常に1行）。
CREATE TABLE IF NOT EXISTS bot_sim_state (
    id           TINYINT  NOT NULL DEFAULT 1,
    last_tick_at DATETIME NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO bot_sim_state (id, last_tick_at)
VALUES (1, NOW())
ON DUPLICATE KEY UPDATE id = id;
