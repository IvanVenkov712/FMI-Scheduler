CREATE TABLE IF NOT EXISTS routes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_room_id INT UNSIGNED NULL,
    to_room_id INT UNSIGNED NULL,
    from_building_id INT UNSIGNED NULL,
    to_building_id INT UNSIGNED NULL,
    name VARCHAR(190) NOT NULL,
    description TEXT NULL,
    movement_type ENUM('walking', 'car', 'public_transport') NOT NULL DEFAULT 'walking',
    distance_meters INT UNSIGNED NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    path_data JSON NULL,
    is_accessible TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('active', 'inactive', 'restricted') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_routes_rooms (from_room_id, to_room_id),
    KEY idx_routes_buildings (from_building_id, to_building_id),
    KEY idx_routes_status (status),
    CONSTRAINT fk_routes_from_room
        FOREIGN KEY (from_room_id) REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_routes_to_room
        FOREIGN KEY (to_room_id) REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_routes_from_building
        FOREIGN KEY (from_building_id) REFERENCES buildings(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_routes_to_building
        FOREIGN KEY (to_building_id) REFERENCES buildings(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS route_steps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    route_id INT UNSIGNED NOT NULL,
    step_order SMALLINT UNSIGNED NOT NULL,
    instruction VARCHAR(255) NOT NULL,
    point_type ENUM('room', 'floor_point', 'building', 'text') NOT NULL DEFAULT 'text',
    room_id INT UNSIGNED NULL,
    floor_point_id INT UNSIGNED NULL,
    building_id INT UNSIGNED NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_route_steps_order (route_id, step_order),
    KEY idx_route_steps_room (room_id),
    KEY idx_route_steps_floor_point (floor_point_id),
    CONSTRAINT fk_route_steps_route
        FOREIGN KEY (route_id) REFERENCES routes(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_route_steps_room
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_route_steps_floor_point
        FOREIGN KEY (floor_point_id) REFERENCES floor_points(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_route_steps_building
        FOREIGN KEY (building_id) REFERENCES buildings(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
