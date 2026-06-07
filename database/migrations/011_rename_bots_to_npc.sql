-- 既存ボットの表示名を「AI投資家◯◯」→「NPC投資家◯◯」へ更新（誤認回避）。
-- 010 は適用済みのため、稼働中DBの既存行はこのマイグレーションで置換する。
UPDATE users
SET name = REPLACE(name, 'AI投資家', 'NPC投資家')
WHERE is_bot = 1 AND name LIKE 'AI投資家%';
