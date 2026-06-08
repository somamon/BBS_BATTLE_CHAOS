-- お問い合わせの控え（メール送信に加えてDBにも残す）。
CREATE TABLE IF NOT EXISTS contact_messages (
    id         VARCHAR(26)  NOT NULL,
    name       VARCHAR(50)  NULL DEFAULT NULL,
    email      VARCHAR(255) NOT NULL,
    message    TEXT         NOT NULL,
    user_id    VARCHAR(26)  NULL DEFAULT NULL,
    ip         CHAR(64)     NULL DEFAULT NULL,
    status     VARCHAR(10)  NOT NULL DEFAULT 'open', -- open | done
    created_at DATETIME     NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_contact_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
