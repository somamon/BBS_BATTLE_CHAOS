-- IP BAN の対象ID（author_hash = SHA-256 / 64文字）が入るよう target_id を拡張。
ALTER TABLE admin_audit_logs MODIFY COLUMN target_id VARCHAR(64) NULL DEFAULT NULL;
