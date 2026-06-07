-- パスワード再設定トークン（生トークンはメールにのみ載せ、DBにはSHA-256ハッシュのみ保存）。M1。
-- email_verifications と同じ設計。乗っ取り対策で有効期間は短い（アプリ側 1時間）。
CREATE TABLE IF NOT EXISTS password_resets (
    token_hash CHAR(64)    NOT NULL,                  -- sha256(生トークン)。完全一致で引く
    user_id    VARCHAR(26) NOT NULL,
    expires_at DATETIME    NOT NULL,                  -- 有効期限（過ぎたら無効）
    created_at DATETIME    NOT NULL,
    PRIMARY KEY (token_hash),
    INDEX idx_password_resets_user (user_id),
    CONSTRAINT fk_password_resets_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
