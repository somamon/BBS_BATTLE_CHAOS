-- 管理画面（フェーズ1）: ロールと凍結状態。
-- role:   user | admin（VARCHARで将来 moderator 等を足せる）
-- status: active | suspended（凍結中はログイン不可）
ALTER TABLE users
    ADD COLUMN role            VARCHAR(10) NOT NULL DEFAULT 'user'   AFTER is_bot,
    ADD COLUMN status          VARCHAR(10) NOT NULL DEFAULT 'active' AFTER role,
    ADD COLUMN suspended_until DATETIME    NULL     DEFAULT NULL     AFTER status;

ALTER TABLE users ADD INDEX idx_users_role (role);
