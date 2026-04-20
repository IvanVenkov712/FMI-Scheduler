CREATE TABLE IF NOT EXISTS resources (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    description TEXT NULL,
    unit VARCHAR(40) NOT NULL DEFAULT 'pcs',
    is_consumable TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_resources_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_resources (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNSIGNED NOT NULL,
    resource_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('available', 'reserved', 'maintenance', 'unavailable') NOT NULL DEFAULT 'available',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_room_resources_room_resource (room_id, resource_id),
    KEY idx_room_resources_resource_id (resource_id),
    CONSTRAINT fk_room_resources_room
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_room_resources_resource
        FOREIGN KEY (resource_id) REFERENCES resources(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resource_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requested_by INT UNSIGNED NULL,
    event_id INT UNSIGNED NULL,
    room_id INT UNSIGNED NULL,
    resource_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('pending', 'approved', 'rejected', 'fulfilled', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_resource_requests_status (status),
    KEY idx_resource_requests_resource_id (resource_id),
    KEY idx_resource_requests_requested_by (requested_by),
    CONSTRAINT fk_resource_requests_resource
        FOREIGN KEY (resource_id) REFERENCES resources(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_resource_requests_requested_by
        FOREIGN KEY (requested_by) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_resource_requests_reviewed_by
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_resource_requests_room
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
