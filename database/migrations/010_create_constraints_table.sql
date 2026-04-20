CREATE TABLE IF NOT EXISTS movement_constraints (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    type ENUM('covid', 'capacity', 'route', 'time_buffer', 'accessibility', 'other') NOT NULL DEFAULT 'covid',
    scope ENUM('global', 'building', 'floor', 'room', 'route') NOT NULL DEFAULT 'global',
    building_id INT UNSIGNED NULL,
    floor_id INT UNSIGNED NULL,
    room_id INT UNSIGNED NULL,
    route_id INT UNSIGNED NULL,
    rule_data JSON NOT NULL,
    description TEXT NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_movement_constraints_active (is_active, type, scope),
    KEY idx_movement_constraints_building (building_id),
    KEY idx_movement_constraints_floor (floor_id),
    KEY idx_movement_constraints_room (room_id),
    KEY idx_movement_constraints_route (route_id),
    CONSTRAINT fk_movement_constraints_building
        FOREIGN KEY (building_id) REFERENCES buildings(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_movement_constraints_floor
        FOREIGN KEY (floor_id) REFERENCES floors(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_movement_constraints_room
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_movement_constraints_route
        FOREIGN KEY (route_id) REFERENCES routes(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_movement_constraints_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS allocation_runs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT UNSIGNED NULL,
    requested_by INT UNSIGNED NULL,
    algorithm VARCHAR(120) NOT NULL DEFAULT 'capacity_resources_distance',
    input_data JSON NULL,
    result_data JSON NULL,
    status ENUM('queued', 'running', 'completed', 'failed') NOT NULL DEFAULT 'queued',
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_allocation_runs_schedule (schedule_id),
    KEY idx_allocation_runs_status (status),
    CONSTRAINT fk_allocation_runs_schedule
        FOREIGN KEY (schedule_id) REFERENCES schedules(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_allocation_runs_requested_by
        FOREIGN KEY (requested_by) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_export_jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    type ENUM('import', 'export') NOT NULL,
    format ENUM('csv', 'xml', 'ascii', 'txt', 'json') NOT NULL,
    entity VARCHAR(80) NOT NULL,
    file_path VARCHAR(500) NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    message TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    KEY idx_import_export_jobs_user (user_id),
    KEY idx_import_export_jobs_status (status),
    CONSTRAINT fk_import_export_jobs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
