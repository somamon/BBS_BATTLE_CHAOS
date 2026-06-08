-- 管理モデレーション: 投稿/スレの運営による非表示（可逆。decayとは別軸）。
ALTER TABLE posts
    ADD COLUMN hidden_at DATETIME    NULL DEFAULT NULL,
    ADD COLUMN hidden_by VARCHAR(26) NULL DEFAULT NULL;
ALTER TABLE threads
    ADD COLUMN hidden_at DATETIME    NULL DEFAULT NULL,
    ADD COLUMN hidden_by VARCHAR(26) NULL DEFAULT NULL;
