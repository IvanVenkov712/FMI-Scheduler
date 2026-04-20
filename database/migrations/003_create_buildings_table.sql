CREATE TABLE IF NOT EXISTS buildings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(160) NOT NULL,
    address VARCHAR(255) NULL,
    description TEXT NULL,
    floors_count INT UNSIGNED NOT NULL DEFAULT 0,
    orientation ENUM('east', 'west', 'north', 'south') NOT NULL DEFAULT 'east',
    schematic_description TEXT NULL,
    vertical_view_east TEXT NULL,
    vertical_view_west TEXT NULL,
    vertical_view_north TEXT NULL,
    vertical_view_south TEXT NULL,
    status ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_buildings_code (code),
    KEY idx_buildings_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
