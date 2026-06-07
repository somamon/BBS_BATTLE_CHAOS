-- ラウンド最終ランキングのスナップショット（M2）。
-- 初期化でゲームデータが消えても順位が残るよう、確定時点の値を写し取る。
-- 表示名も保存する（後でユーザーが退会してもランキングは残す）。
CREATE TABLE IF NOT EXISTS round_rankings (
    round_id    BIGINT      NOT NULL,
    rank_no     INT         NOT NULL,                  -- 1位から
    user_id     VARCHAR(26) NULL DEFAULT NULL,         -- 退会で消えても順位は残すため NULL 許容
    name        VARCHAR(50) NOT NULL,                  -- 確定時点の表示名スナップショット
    cash        INT         NOT NULL,
    share_value BIGINT      NOT NULL,
    total       BIGINT      NOT NULL,
    PRIMARY KEY (round_id, rank_no),
    INDEX idx_round_rankings_round (round_id),
    CONSTRAINT fk_round_rankings_round
        FOREIGN KEY (round_id) REFERENCES rounds (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
