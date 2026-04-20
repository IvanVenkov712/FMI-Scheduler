CREATE TABLE IF NOT EXISTS schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    type ENUM('semester', 'exam_session', 'ad_hoc') NOT NULL DEFAULT 'semester',
    academic_year VARCHAR(20) NULL,
    semester ENUM('winter', 'summer', 'none') NOT NULL DEFAULT 'none',
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_schedules_status_dates (status, starts_on, ends_on),
    KEY idx_schedules_created_by (created_by),
    CONSTRAINT fk_schedules_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT chk_schedules_dates CHECK (ends_on >= starts_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schedule_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    assigned_room_id INT UNSIGNED NULL,
    allocation_status ENUM('manual', 'auto_assigned', 'conflict', 'unassigned') NOT NULL DEFAULT 'unassigned',
    conflict_notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_schedule_events (schedule_id, event_id),
    KEY idx_schedule_events_event_id (event_id),
    KEY idx_schedule_events_room_id (assigned_room_id),
    CONSTRAINT fk_schedule_events_schedule
        FOREIGN KEY (schedule_id) REFERENCES schedules(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_schedule_events_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_schedule_events_room
        FOREIGN KEY (assigned_room_id) REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_unavailability (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNSIGNED NOT NULL,
    reason VARCHAR(190) NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_room_unavailability_room_time (room_id, starts_at, ends_at),
    CONSTRAINT fk_room_unavailability_room
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_room_unavailability_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT chk_room_unavailability_time CHECK (ends_at > starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
