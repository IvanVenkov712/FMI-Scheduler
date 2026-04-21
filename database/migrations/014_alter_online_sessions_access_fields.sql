ALTER TABLE online_sessions
    ADD COLUMN access_password VARCHAR(255) NULL AFTER access_key,
    ADD COLUMN moderator_name VARCHAR(160) NULL AFTER host_user_id,
    ADD COLUMN access_instructions TEXT NULL AFTER moderator_name;
