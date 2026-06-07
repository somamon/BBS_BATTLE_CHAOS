-- レート制限カウンタ（キー単位の試行回数と失効時刻。ブルートフォース/ボット対策）
CREATE TABLE IF NOT EXISTS rate_limits (
    rl_key     VARCHAR(191) NOT NULL,                 -- 例: "register:203.0.113.1"
    attempts   INT          NOT NULL DEFAULT 0,
    expires_at DATETIME     NOT NULL,                 -- このウィンドウの失効時刻
    PRIMARY KEY (rl_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
