-- レス（投資対象外。時間で朽ちて流れる）
CREATE TABLE IF NOT EXISTS posts (
    id            VARCHAR(26) NOT NULL,
    thread_id     VARCHAR(26) NOT NULL,
    author_hash   VARCHAR(64) NOT NULL,                  -- 匿名識別（IPハッシュ）
    author_id     VARCHAR(26) NULL DEFAULT NULL,         -- 登録ユーザーなら紐付け（表示用）
    content       TEXT        NOT NULL,
    hp            INT         NOT NULL DEFAULT 100,
    decay_per_min INT         NOT NULL DEFAULT 5,
    last_decay_at DATETIME    NOT NULL,
    status        VARCHAR(10) NOT NULL DEFAULT 'alive',
    created_at    DATETIME    NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_posts_thread (thread_id, status),
    CONSTRAINT fk_posts_thread
        FOREIGN KEY (thread_id) REFERENCES threads (id) ON DELETE CASCADE,
    CONSTRAINT fk_posts_author
        FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
