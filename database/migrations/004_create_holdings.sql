-- 持ち分（ユーザー×投稿(post)の保有株数と取得原価）
CREATE TABLE IF NOT EXISTS holdings (
    user_id VARCHAR(26) NOT NULL,
    post_id VARCHAR(26) NOT NULL,
    shares  INT         NOT NULL DEFAULT 0,
    cost    BIGINT      NOT NULL DEFAULT 0,   -- 取得原価（株購入に支払った累計額。含み損益算出用）
    PRIMARY KEY (user_id, post_id),
    INDEX idx_holdings_post (post_id),
    CONSTRAINT fk_holdings_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_holdings_post
        FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
