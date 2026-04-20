INSERT INTO users (id, role_id, username, email, password_hash, first_name, last_name, department, status) VALUES
    (1, 1, 'admin', 'admin@example.test', '$2y$10$abcdefghijklmnopqrstuuCzQ1f9bNw3t7iMh5Qk6dVJ2Q0uV3r1u', 'System', 'Admin', 'IT', 'active'),
    (2, 2, 'scheduler', 'scheduler@example.test', '$2y$10$abcdefghijklmnopqrstuuCzQ1f9bNw3t7iMh5Qk6dVJ2Q0uV3r1u', 'Schedule', 'Manager', 'Academic Office', 'active'),
    (3, 3, 'teacher', 'teacher@example.test', '$2y$10$abcdefghijklmnopqrstuuCzQ1f9bNw3t7iMh5Qk6dVJ2Q0uV3r1u', 'Demo', 'Teacher', 'Computer Science', 'active'),
    (4, 4, 'student', 'student@example.test', '$2y$10$abcdefghijklmnopqrstuuCzQ1f9bNw3t7iMh5Qk6dVJ2Q0uV3r1u', 'Demo', 'Student', 'Computer Science', 'active'),
    (5, 5, 'technician', 'technician@example.test', '$2y$10$abcdefghijklmnopqrstuuCzQ1f9bNw3t7iMh5Qk6dVJ2Q0uV3r1u', 'Technical', 'Staff', 'Facilities', 'active')
ON DUPLICATE KEY UPDATE
    role_id = VALUES(role_id),
    email = VALUES(email),
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    department = VALUES(department),
    status = VALUES(status),
    updated_at = CURRENT_TIMESTAMP;
