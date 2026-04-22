ALTER TABLE exam_requests
    ADD COLUMN online_platform VARCHAR(100) NULL AFTER preferred_ends_at,
    ADD COLUMN online_url VARCHAR(500) NULL AFTER online_platform,
    ADD COLUMN online_access_key VARCHAR(255) NULL AFTER online_url,
    ADD COLUMN online_access_password VARCHAR(255) NULL AFTER online_access_key,
    ADD COLUMN online_host_user_id INT UNSIGNED NULL AFTER online_access_password,
    ADD COLUMN online_moderator_name VARCHAR(160) NULL AFTER online_host_user_id,
    ADD COLUMN online_access_instructions TEXT NULL AFTER online_moderator_name,
    ADD CONSTRAINT fk_exam_requests_online_host_user
        FOREIGN KEY (online_host_user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;
