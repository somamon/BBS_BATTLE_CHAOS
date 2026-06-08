-- 株の売却（リザーブ式ボンディングカーブ）。買いの株購入分(70%)を投稿のリザーブに積み、
-- 売却でリザーブから現金を払い戻す（鮮度で減額）。HP回復分(30%)は従来どおり焼却(sink)。
-- 既存投稿の reserve は 0（旧ルールでは買い資金は焼却済みのため）。公開前はラウンドリセット推奨。
ALTER TABLE posts ADD COLUMN reserve BIGINT NOT NULL DEFAULT 0 AFTER total_shares;
