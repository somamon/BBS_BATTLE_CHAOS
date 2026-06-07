-- Googleアカウントログイン（OAuth/OIDC）対応。
-- google_sub = Google の一意ユーザーID（OIDC の sub）。アカウント連携のキー。
-- パスワード未設定（Googleのみ）のユーザーを許すため password_hash を NULL 許容に変更。
ALTER TABLE users
    ADD COLUMN google_sub VARCHAR(255) NULL DEFAULT NULL AFTER password_hash,
    MODIFY COLUMN password_hash VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE users
    ADD UNIQUE KEY uniq_users_google_sub (google_sub);
