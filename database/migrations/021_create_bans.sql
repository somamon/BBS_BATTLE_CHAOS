-- BAN（IPハッシュ / ユーザー）。expires_at NULL は無期限。
CREATE TABLE IF NOT EXISTS bans (
    id         BIGINT       NOT NULL AUTO_INCREMENT,
    kind       VARCHAR(10)  NOT NULL,             -- ip | user
    value      VARCHAR(64)  NOT NULL,             -- ip_hash または user_id
    reason     VARCHAR(200) NULL DEFAULT NULL,
    created_by VARCHAR(26)  NULL DEFAULT NULL,
    expires_at DATETIME     NULL DEFAULT NULL,
    created_at DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_bans_kind_value (kind, value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
