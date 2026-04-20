CREATE TABLE IF NOT EXISTS floors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    building_id INT UNSIGNED NOT NULL,
    number INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    plan_data LONGTEXT NULL,
    ascii_plan LONGTEXT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 0,
    width_units INT UNSIGNED NULL,
    height_units INT UNSIGNED NULL,
    status ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_floors_building_number (building_id, number),
    KEY idx_floors_building_id (building_id),
    CONSTRAINT fk_floors_building
        FOREIGN KEY (building_id) REFERENCES buildings(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS floor_points (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    floor_id INT UNSIGNED NOT NULL,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(120) NOT NULL,
    type ENUM('entrance', 'exit', 'stairs', 'elevator', 'corridor', 'node') NOT NULL,
    x INT NOT NULL DEFAULT 0,
    y INT NOT NULL DEFAULT 0,
    is_accessible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_floor_points_code (floor_id, code),
    KEY idx_floor_points_floor_type (floor_id, type),
    CONSTRAINT fk_floor_points_floor
        FOREIGN KEY (floor_id) REFERENCES floors(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
