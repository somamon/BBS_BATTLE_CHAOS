-- 投資の監査ログ（約定内訳。追記専用。doc21 §4）
CREATE TABLE IF NOT EXISTS investments (
    id          VARCHAR(26)   NOT NULL,
    investor_id VARCHAR(26)   NOT NULL,
    post_id     VARCHAR(26)   NOT NULL,                  -- 対象投稿
    amount      INT           NOT NULL,                  -- 投資総額
    shares      INT           NOT NULL,                  -- 取得株数
    price       DECIMAL(12,4) NOT NULL,                  -- 約定時の株価spot
    to_shares   INT           NOT NULL,                  -- 株取得に回した額（70%）
    to_hp       INT           NOT NULL,                  -- HP回復に回した額（30%）
    created_at  DATETIME      NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_investments_investor (investor_id),
    INDEX idx_investments_post (post_id),
    CONSTRAINT fk_investments_investor
        FOREIGN KEY (investor_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_investments_post
        FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
