-- スレッド＝板（コンテナ）。投資対象ではなく、HPが朽ちる「寿命」のみ持つ（doc21 §4）。
CREATE TABLE IF NOT EXISTS threads (
    id             VARCHAR(26)  NOT NULL,
    creator_id     VARCHAR(26)  NULL DEFAULT NULL,        -- 立てた登録ユーザー（匿名は NULL）
    title          VARCHAR(255) NOT NULL,
    hp             INT          NOT NULL DEFAULT 300,      -- 確定HP（スナップショット）
    max_hp         INT          NOT NULL DEFAULT 1000,     -- HP上限（板は固定）
    decay_per_min  INT          NOT NULL DEFAULT 5,        -- 減衰レート（基礎値）
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
