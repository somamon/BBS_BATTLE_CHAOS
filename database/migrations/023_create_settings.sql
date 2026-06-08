-- ランタイム設定（ゲームバランスのDB上書き・メンテモード・アナウンス等）。
CREATE TABLE IF NOT EXISTS settings (
    k          VARCHAR(60)  NOT NULL,
    v          VARCHAR(255) NOT NULL,
    updated_at DATETIME     NOT NULL,
    PRIMARY KEY (k)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
