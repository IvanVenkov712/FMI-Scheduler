USE schedule_management_system;
INSERT INTO users (username, first_name, last_name, email, password_hash, `role`, is_active)
VALUES ('schedule_manager', 'Schedule', 'Managerov', 'schedule_manager@schedule.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'schedule_manager', 1)