CREATE DATABASE IF NOT EXISTS `schedule_management_system`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `schedule_management_system`;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL DEFAULT '',
    `last_name` VARCHAR(100) NOT NULL DEFAULT '',
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'student',
    `is_active` BOOLEAN NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `username` VARCHAR(50) NULL,
    `action` VARCHAR(150) NOT NULL,
    `method` VARCHAR(10) NOT NULL,
    `path` VARCHAR(500) NOT NULL,
    `entity_type` VARCHAR(100) NULL,
    `entity_id` VARCHAR(100) NULL,
    `ip_address` VARCHAR(45) NULL,
    `details` JSON NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_logs_created_at` (`created_at`),
    KEY `idx_audit_logs_user_created` (`user_id`, `created_at`),
    KEY `idx_audit_logs_action_created` (`action`, `created_at`),
    CONSTRAINT `fk_audit_logs_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `buildings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `address` VARCHAR(255) NOT NULL DEFAULT '',
    `campus_area` VARCHAR(100) NOT NULL DEFAULT '',
    `floor_count` INT NOT NULL DEFAULT 0,
    `is_active` BOOLEAN NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_buildings_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `floors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `building_id` INT UNSIGNED NOT NULL,
    `level` INT NOT NULL DEFAULT 0,
    `name` VARCHAR(100) NOT NULL DEFAULT '',
    `ascii_plan` MEDIUMTEXT NOT NULL,
    `notes` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_floors_building_level` (`building_id`, `level`),
    CONSTRAINT `fk_floors_building`
        FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rooms` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `floor_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL DEFAULT '',
    `position_code` VARCHAR(50) NOT NULL DEFAULT '',
    `room_type` VARCHAR(50) NOT NULL DEFAULT 'physical',
    `capacity` INT NOT NULL DEFAULT 0,
    `room_size` VARCHAR(50) NOT NULL DEFAULT 'small',
    `is_active` BOOLEAN NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_rooms_floor_position_code` (`floor_id`, `position_code`),
    CONSTRAINT `fk_rooms_floor`
        FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resources` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_resources_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_resources` (
    `resource_id` INT UNSIGNED NOT NULL,
    `room_id` INT UNSIGNED NOT NULL,
    `resource_quantity` INT NOT NULL DEFAULT 1,
    PRIMARY KEY (`resource_id`, `room_id`),
    CONSTRAINT `fk_room_resources_resource`
        FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_room_resources_room`
        FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `online_access` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `platform` VARCHAR(100) NOT NULL DEFAULT '',
    `meeting_link` VARCHAR(500) NOT NULL DEFAULT '',
    `access_key` VARCHAR(255) NOT NULL DEFAULT '',
    `host_notes` TEXT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `events` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_id` INT UNSIGNED NULL,
    `online_access_id` INT UNSIGNED NULL,
    `requestor_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL DEFAULT '',
    `type` VARCHAR(50) NOT NULL DEFAULT 'lecture',
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `delivery_mode` VARCHAR(50) NOT NULL DEFAULT 'physical',
    `subject_name` VARCHAR(150) NOT NULL DEFAULT '',
    `student_count` INT NOT NULL DEFAULT 0,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_events_room`
        FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_events_online_access`
        FOREIGN KEY (`online_access_id`) REFERENCES `online_access` (`id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT `fk_events_user`
        FOREIGN KEY (`requestor_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedules` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL DEFAULT '',
    `description` TEXT NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resource_requirements` (
    `event_id` INT UNSIGNED NOT NULL,
    `resource_id` INT UNSIGNED NOT NULL,
    `required_quantity` INT NOT NULL DEFAULT 1,
    `notes` TEXT NULL,
    PRIMARY KEY (`event_id`, `resource_id`),
    UNIQUE KEY `uq_resource_requirements_request_resource` (`event_id`, `resource_id`),
    CONSTRAINT `fk_resource_requirements_event`
        FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_resource_requirements_resource`
        FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `route_segments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `from_room_id` INT UNSIGNED NOT NULL,
    `to_room_id` INT UNSIGNED NOT NULL,
    `distance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `corridor_capacity` INT NOT NULL DEFAULT 0,
    `is_stairs` TINYINT(1) NOT NULL DEFAULT 0,
    `is_elevator` TINYINT(1) NOT NULL DEFAULT 0,
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_route_segments_from_to` (`from_room_id`, `to_room_id`),
    CONSTRAINT `fk_route_segments_from_room`
        FOREIGN KEY (`from_room_id`) REFERENCES `rooms` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_route_segments_to_room`
        FOREIGN KEY (`to_room_id`) REFERENCES `rooms` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `movement_constraints` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `route_segment_id` INT UNSIGNED NOT NULL,
    `constraint_type` VARCHAR(50) NOT NULL DEFAULT '',
    `max_capacity` INT NOT NULL DEFAULT 0,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `notes` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_movement_constraints_segment_time` (`route_segment_id`, `start_time`, `end_time`),
    CONSTRAINT `fk_movement_constraints_route_segment`
        FOREIGN KEY (`route_segment_id`) REFERENCES `route_segments` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedule_events` (
    `schedule_id` INT UNSIGNED NOT NULL,
    `event_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`schedule_id`, `event_id`),
    CONSTRAINT `fk_schedule_events_schedule`
        FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_schedule_events_event`
        FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
