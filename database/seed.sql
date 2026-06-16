INSERT IGNORE INTO users (username, first_name, last_name, email, password_hash, `role`, is_active)
VALUES ('admin', 'Admin', 'Adminov', 'admin@admin.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'admin', 1);

INSERT IGNORE INTO users (username, first_name, last_name, email, password_hash, `role`, is_active)
VALUES ('teacher', 'Teacher', 'Teacherov', 'teacher@teacher.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'teacher', 1);

INSERT IGNORE INTO users (username, first_name, last_name, email, password_hash, `role`, is_active)
VALUES ('schedule_manager', 'Schedule', 'Managerov', 'schedule_manager@schedule.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'schedule_manager', 1);
