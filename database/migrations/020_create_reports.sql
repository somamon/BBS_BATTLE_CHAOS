-- 通報（公開側から投稿/スレを通報し、管理側で対応）。
CREATE TABLE IF NOT EXISTS reports (
    id          VARCHAR(26)  NOT NULL,
    target_type VARCHAR(10)  NOT NULL,            -- post | thread
    target_id   VARCHAR(26)  NOT NULL,
    reason      VARCHAR(20)  NOT NULL,            -- spam | abuse | illegal | other
    detail      VARCHAR(500) NULL DEFAULT NULL,
    reporter_id VARCHAR(26)  NULL DEFAULT NULL,
    reporter_ip CHAR(64)     NOT NULL,            -- ハッシュ
    status      VARCHAR(10)  NOT NULL DEFAULT 'open', -- open | resolved | rejected
    created_at  DATETIME     NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_reports_status (status, created_at),
    INDEX idx_reports_target (target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
