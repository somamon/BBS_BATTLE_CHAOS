-- 持ち分（ユーザー×スレッドの保有株数）
CREATE TABLE IF NOT EXISTS holdings (
    user_id   VARCHAR(26) NOT NULL,
    thread_id VARCHAR(26) NOT NULL,
    shares    INT         NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id, thread_id),
    INDEX idx_holdings_thread (thread_id),
    CONSTRAINT fk_holdings_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_holdings_thread
        FOREIGN KEY (thread_id) REFERENCES threads (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
