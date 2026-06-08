-- 管理操作の監査ログ（追記専用）。誰が・いつ・何を・対象・詳細を記録する。
CREATE TABLE IF NOT EXISTS admin_audit_logs (
    id          BIGINT      NOT NULL AUTO_INCREMENT,
    admin_id    VARCHAR(26) NOT NULL,                  -- 操作した管理者（CLIは 'cli'）
    action      VARCHAR(40) NOT NULL,                  -- user.suspend / user.promote ...
    target_type VARCHAR(20) NULL DEFAULT NULL,         -- user / post / thread ...
    target_id   VARCHAR(40) NULL DEFAULT NULL,
    detail      VARCHAR(500) NULL DEFAULT NULL,        -- 補足（変更前後など。JSON文字列でも可）
    ip          VARCHAR(45) NULL DEFAULT NULL,
    created_at  DATETIME    NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_audit_admin (admin_id, created_at),
    INDEX idx_audit_action (action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
