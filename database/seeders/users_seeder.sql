INSERT INTO users (
    id, role_id, username, email, password_hash, first_name, last_name,
    phone, faculty_number, department, status, last_login_at, remember_token
) VALUES
    (1, 1, 'admin', 'admin@example.test', '$2y$10$abcdefghijklmnopqrstuuCzQ1f9bNw3t7iMh5Qk6dVJ2Q0uV3r1u', 'System', 'Admin', '+359888000001', NULL, 'IT', 'active', '2026-04-01 09:00:00', NULL),
    (2, 2, 'scheduler', 'scheduler@example.test', '$2y$10$abcdefghijklmnopqrstuuCzQ1f9bNw3t7iMh5Qk6dVJ2Q0uV3r1u', 'Schedule', 'Manager', '+359888000002', NULL, 'Academic Office', 'active', '2026-04-02 08:30:00', NULL),
    (3, 3, 'teacher', 'teacher@example.test', '$2y$10$abcdefghijklmnopqrstuuCzQ1f9bNw3t7iMh5Qk6dVJ2Q0uV3r1u', 'Demo', 'Teacher', '+359888000003', 'FMI-T-1001', 'Computer Science', 'active', '2026-04-03 10:15:00', NULL),
    (4, 4, 'student', 'student@example.test', '$2y$10$abcdefghijklmnopqrstuuCzQ1f9bNw3t7iMh5Qk6dVJ2Q0uV3r1u', 'Demo', 'Student', '+359888000004', 'FMI-S-2001', 'Computer Science', 'active', '2026-04-04 11:45:00', NULL),
    (5, 5, 'technician', 'technician@example.test', '$2y$10$abcdefghijklmnopqrstuuCzQ1f9bNw3t7iMh5Qk6dVJ2Q0uV3r1u', 'Technical', 'Staff', '+359888000005', NULL, 'Facilities', 'active', '2026-04-05 07:50:00', NULL)
ON DUPLICATE KEY UPDATE
    role_id = VALUES(role_id),
    email = VALUES(email),
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    phone = VALUES(phone),
    faculty_number = VALUES(faculty_number),
    department = VALUES(department),
    status = VALUES(status),
    last_login_at = VALUES(last_login_at),
    remember_token = VALUES(remember_token),
    updated_at = CURRENT_TIMESTAMP;
