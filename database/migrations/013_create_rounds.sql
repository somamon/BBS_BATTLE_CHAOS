-- ラウンド（M2）。終局→ランキング確定→初期化→再開のサイクルを管理する。
-- 常に「進行中（ended_at IS NULL）」の行が1つだけ存在する。番号 = id。
CREATE TABLE IF NOT EXISTS rounds (
    id         BIGINT      NOT NULL AUTO_INCREMENT,
    started_at DATETIME    NOT NULL,
    ended_at   DATETIME    NULL DEFAULT NULL,          -- NULL=進行中。確定時に記録
    reason     VARCHAR(20) NULL DEFAULT NULL,          -- 終局理由（time_up / all_dead / manual）
    PRIMARY KEY (id),
    INDEX idx_rounds_ended (ended_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 初回ラウンドを開始（進行中）。既に行があれば何もしない。
-- MySQL は FROM 無しの WHERE を許さないため FROM DUAL を使う。
INSERT INTO rounds (id, started_at, ended_at, reason)
SELECT 1, NOW(), NULL, NULL FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM rounds);
