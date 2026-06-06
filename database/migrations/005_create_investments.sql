-- 投資の監査ログ（配分内訳。追記専用）
CREATE TABLE IF NOT EXISTS investments (
    id          VARCHAR(26) NOT NULL,
    investor_id VARCHAR(26) NOT NULL,
    thread_id   VARCHAR(26) NOT NULL,
    amount      INT         NOT NULL,                    -- 投資総額
    to_hp       INT         NOT NULL,                    -- HPへ充当した額
    to_dividend INT         NOT NULL,                    -- 配当に回した額
    to_sink     INT         NOT NULL,                    -- 消滅額
    created_at  DATETIME    NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_investments_investor (investor_id),
    INDEX idx_investments_thread (thread_id),
    CONSTRAINT fk_investments_investor
        FOREIGN KEY (investor_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_investments_thread
        FOREIGN KEY (thread_id) REFERENCES threads (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
