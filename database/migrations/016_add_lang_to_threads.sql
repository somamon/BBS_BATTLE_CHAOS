-- スレッドの言語（表示ロケール別の一覧表示用）。既存スレは 'ja' 扱い。
-- 経済（株価・所持金・ランキング）は全言語共通のまま。分離するのは一覧表示だけ。
ALTER TABLE threads
    ADD COLUMN lang VARCHAR(5) NOT NULL DEFAULT 'ja' AFTER creator_id;

-- 一覧（言語×状態×新着順）と墓場（言語×状態×朽ちた順）の索引。
ALTER TABLE threads
    ADD INDEX idx_threads_lang_status_created (lang, status, created_at),
    ADD INDEX idx_threads_lang_status_updated (lang, status, updated_at);
