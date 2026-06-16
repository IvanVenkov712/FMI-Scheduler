USE schedule_management_system;
INSERT INTO users (username, first_name, last_name, email, password_hash, `role`, is_active)
VALUES ('teacher', 'Teacher', 'Teacherov', 'teacher@teacher.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'teacher', 1)