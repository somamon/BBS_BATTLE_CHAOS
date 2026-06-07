-- レス＝投稿（doc21 で投資対象に昇格。株価・レベル・HPを持つ）
CREATE TABLE IF NOT EXISTS posts (
    id             VARCHAR(26) NOT NULL,
    thread_id      VARCHAR(26) NOT NULL,
    author_hash    VARCHAR(64) NOT NULL,                  -- 匿名識別（IPハッシュ）
    author_id      VARCHAR(26) NULL DEFAULT NULL,         -- 登録ユーザーなら紐付け（表示用）
    content        TEXT        NOT NULL,
    hp             INT         NOT NULL DEFAULT 100,
    max_hp         INT         NOT NULL DEFAULT 100,       -- HP上限（レベルで上昇）
    decay_per_min  INT         NOT NULL DEFAULT 5,
    total_invested BIGINT      NOT NULL DEFAULT 0,         -- 累計投資額（株価・レベルの基礎）
    total_shares   INT         NOT NULL DEFAULT 0,         -- 発行済み総株数
    level          TINYINT     NOT NULL DEFAULT 0,         -- 0=新規 / 1=注目 / 2=人気 / 3=殿堂入り
    last_decay_at  DATETIME    NOT NULL,
    status         VARCHAR(10) NOT NULL DEFAULT 'alive',
    created_at     DATETIME    NOT NULL,
    updated_at     DATETIME    NOT NULL,                   -- 株/HP確定で更新
    PRIMARY KEY (id),
    INDEX idx_posts_thread (thread_id, status),
    INDEX idx_posts_status_invested (status, total_invested), -- 人気順・ランキング集計用
    CONSTRAINT fk_posts_thread
        FOREIGN KEY (thread_id) REFERENCES threads (id) ON DELETE CASCADE,
    CONSTRAINT fk_posts_author
        FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
