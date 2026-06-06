-- スレッド（投資対象。HPが朽ち、累計投資で変異する）
CREATE TABLE IF NOT EXISTS threads (
    id             VARCHAR(26)  NOT NULL,
    creator_id     VARCHAR(26)  NULL DEFAULT NULL,        -- 立てた登録ユーザー（匿名は NULL）
    title          VARCHAR(255) NOT NULL,
    hp             INT          NOT NULL DEFAULT 300,      -- 確定HP（スナップショット）
    max_hp         INT          NOT NULL DEFAULT 1000,     -- HP上限（変異で上昇）
    decay_per_min  INT          NOT NULL DEFAULT 5,        -- 減衰レート（基礎値）
    mutation_level INT          NOT NULL DEFAULT 0,        -- 変異段階（0〜3）
    total_shares   INT          NOT NULL DEFAULT 0,        -- 発行済み総株数
    last_decay_at  DATETIME     NOT NULL,                  -- HP確定時刻
    status         VARCHAR(10)  NOT NULL DEFAULT 'alive',  -- alive / dead
    post_count     INT          NOT NULL DEFAULT 0,        -- レス数（勢いの目安）
    created_at     DATETIME     NOT NULL,
    updated_at     DATETIME     NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_threads_status_created (status, created_at),
    CONSTRAINT fk_threads_creator
        FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
