CREATE TABLE IF NOT EXISTS room_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    description TEXT NULL,
    is_physical TINYINT(1) NOT NULL DEFAULT 1,
    supports_online TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_room_types_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    floor_id INT UNSIGNED NULL,
    room_type_id INT UNSIGNED NOT NULL,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 0,
    covid_capacity INT UNSIGNED NULL,
    x INT NULL,
    y INT NULL,
    width INT NULL,
    height INT NULL,
    online_url VARCHAR(500) NULL,
    access_key VARCHAR(255) NULL,
    status ENUM('available', 'occupied', 'maintenance', 'unavailable') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rooms_code (code),
    KEY idx_rooms_floor_id (floor_id),
    KEY idx_rooms_room_type_id (room_type_id),
    KEY idx_rooms_status_capacity (status, capacity),
    CONSTRAINT fk_rooms_floor
        FOREIGN KEY (floor_id) REFERENCES floors(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_rooms_room_type
        FOREIGN KEY (room_type_id) REFERENCES room_types(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_connections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_room_id INT UNSIGNED NOT NULL,
    to_room_id INT UNSIGNED NOT NULL,
    connection_type ENUM('corridor', 'stairs', 'elevator', 'door', 'outdoor') NOT NULL DEFAULT 'corridor',
    is_bidirectional TINYINT(1) NOT NULL DEFAULT 1,
    walking_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_room_connections_pair (from_room_id, to_room_id, connection_type),
    KEY idx_room_connections_to_room (to_room_id),
    CONSTRAINT fk_room_connections_from_room
        FOREIGN KEY (from_room_id) REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_room_connections_to_room
        FOREIGN KEY (to_room_id) REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
