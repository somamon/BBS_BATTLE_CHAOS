-- NPC投資家（ボット）の初期投入。is_bot=1・メール確認済み・ログイン不可（ダミーハッシュ）。
-- 所持金は人間(初期500)より多めにして相場に厚みを出す。id は 26 文字固定。
INSERT INTO users (id, email, name, password_hash, money, email_verified_at, is_bot, created_at)
VALUES
  ('BOT00000000000000000000001', 'bot1@bots.local',  'AI投資家アルファ',   '$2y$10$N4L5SZYhox74NHw490rtZ.qr4c.zaN8/aSc/wQhh/XTzl2D7qf25C', 5000, NOW(), 1, NOW()),
  ('BOT00000000000000000000002', 'bot2@bots.local',  'AI投資家ベータ',     '$2y$10$N4L5SZYhox74NHw490rtZ.qr4c.zaN8/aSc/wQhh/XTzl2D7qf25C', 5000, NOW(), 1, NOW()),
  ('BOT00000000000000000000003', 'bot3@bots.local',  'AI投資家ガンマ',     '$2y$10$N4L5SZYhox74NHw490rtZ.qr4c.zaN8/aSc/wQhh/XTzl2D7qf25C', 5000, NOW(), 1, NOW()),
  ('BOT00000000000000000000004', 'bot4@bots.local',  'AI投資家デルタ',     '$2y$10$N4L5SZYhox74NHw490rtZ.qr4c.zaN8/aSc/wQhh/XTzl2D7qf25C', 5000, NOW(), 1, NOW()),
  ('BOT00000000000000000000005', 'bot5@bots.local',  'AI投資家イプシロン', '$2y$10$N4L5SZYhox74NHw490rtZ.qr4c.zaN8/aSc/wQhh/XTzl2D7qf25C', 5000, NOW(), 1, NOW()),
  ('BOT00000000000000000000006', 'bot6@bots.local',  'AI投資家ゼータ',     '$2y$10$N4L5SZYhox74NHw490rtZ.qr4c.zaN8/aSc/wQhh/XTzl2D7qf25C', 5000, NOW(), 1, NOW()),
  ('BOT00000000000000000000007', 'bot7@bots.local',  'AI投資家イータ',     '$2y$10$N4L5SZYhox74NHw490rtZ.qr4c.zaN8/aSc/wQhh/XTzl2D7qf25C', 5000, NOW(), 1, NOW()),
  ('BOT00000000000000000000008', 'bot8@bots.local',  'AI投資家シータ',     '$2y$10$N4L5SZYhox74NHw490rtZ.qr4c.zaN8/aSc/wQhh/XTzl2D7qf25C', 5000, NOW(), 1, NOW()),
  ('BOT00000000000000000000009', 'bot9@bots.local',  'AI投資家イオタ',     '$2y$10$N4L5SZYhox74NHw490rtZ.qr4c.zaN8/aSc/wQhh/XTzl2D7qf25C', 5000, NOW(), 1, NOW()),
  ('BOT00000000000000000000010', 'bot10@bots.local', 'AI投資家カッパ',     '$2y$10$N4L5SZYhox74NHw490rtZ.qr4c.zaN8/aSc/wQhh/XTzl2D7qf25C', 5000, NOW(), 1, NOW())
ON DUPLICATE KEY UPDATE id = id;
