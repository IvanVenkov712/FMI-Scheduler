CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_by INT UNSIGNED NULL,
    room_id INT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    type ENUM('exam', 'lecture', 'exercise', 'consultation', 'other') NOT NULL DEFAULT 'lecture',
    description TEXT NULL,
    course_name VARCHAR(190) NULL,
    group_name VARCHAR(120) NULL,
    students_count INT UNSIGNED NOT NULL DEFAULT 0,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    delivery_mode ENUM('physical', 'online', 'hybrid') NOT NULL DEFAULT 'physical',
    status ENUM('draft', 'requested', 'scheduled', 'cancelled', 'completed') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_events_room_time (room_id, starts_at, ends_at),
    KEY idx_events_type_status (type, status),
    KEY idx_events_created_by (created_by),
    CONSTRAINT fk_events_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_events_room
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT chk_events_time CHECK (ends_at > starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS online_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    platform VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    access_key VARCHAR(255) NULL,
    host_user_id INT UNSIGNED NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_online_sessions_event (event_id),
    KEY idx_online_sessions_host_user (host_user_id),
    CONSTRAINT fk_online_sessions_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_online_sessions_host_user
        FOREIGN KEY (host_user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requested_by INT UNSIGNED NULL,
    event_id INT UNSIGNED NULL,
    course_name VARCHAR(190) NOT NULL,
    students_count INT UNSIGNED NOT NULL,
    preferred_room_id INT UNSIGNED NULL,
    preferred_delivery_mode ENUM('physical', 'online', 'hybrid') NOT NULL DEFAULT 'physical',
    preferred_starts_at DATETIME NULL,
    preferred_ends_at DATETIME NULL,
    required_resources JSON NULL,
    notes TEXT NULL,
    status ENUM('pending', 'approved', 'rejected', 'scheduled', 'cancelled') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_exam_requests_status (status),
    KEY idx_exam_requests_requested_by (requested_by),
    KEY idx_exam_requests_event_id (event_id),
    CONSTRAINT fk_exam_requests_requested_by
        FOREIGN KEY (requested_by) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_exam_requests_reviewed_by
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_exam_requests_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_exam_requests_preferred_room
        FOREIGN KEY (preferred_room_id) REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE resource_requests
    ADD CONSTRAINT fk_resource_requests_event
    FOREIGN KEY (event_id) REFERENCES events(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL;
