-- 登録投資家（設計: docs/design/05_DB設計.md）
CREATE TABLE IF NOT EXISTS users (
    id            VARCHAR(26)  NOT NULL,
    email         VARCHAR(255) NOT NULL,                 -- ログインID（一意）
    name          VARCHAR(50)  NOT NULL,                 -- 表示名
    password_hash VARCHAR(255) NOT NULL,                 -- password_hash() の出力
    money         INT          NOT NULL DEFAULT 500,     -- 所持金
    created_at    DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
