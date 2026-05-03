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

CREATE TABLE IF NOT EXISTS `events` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_id` INT UNSIGNED NOT NULL,
    `requestor_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL DEFAULT '',
    `type` VARCHAR(50) NOT NULL DEFAULT 'lecture',
    `date` DATE NULL,
    `start_time` TIME NULL,
    `end_time` TIME NULL,
    `delivery_mode` VARCHAR(50) NOT NULL DEFAULT 'physical',
    `subject_name` VARCHAR(150) NOT NULL DEFAULT '',
    `student_count` INT NOT NULL DEFAULT 0,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    PRIMARY KEY (`id`),
    KEY `idx_events_room_date_time` (`room_id`, `date`, `start_time`),
    CONSTRAINT `fk_events_room`
        FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
     CONSTRAINT `fk_events_user`
        FOREIGN KEY (`requestor_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resource_requirements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_request_id` INT UNSIGNED NOT NULL,
    `resource_id` INT UNSIGNED NOT NULL,
    `required_quantity` INT NOT NULL DEFAULT 1,
    `notes` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_resource_requirements_request_resource` (`event_request_id`, `resource_id`),
    CONSTRAINT `fk_resource_requirements_event_request`
        FOREIGN KEY (`event_request_id`) REFERENCES `event_requests` (`id`)
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

CREATE TABLE IF NOT EXISTS `online_access` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_request_id` INT UNSIGNED NOT NULL,
    `platform` VARCHAR(100) NOT NULL DEFAULT '',
    `meeting_link` VARCHAR(500) NOT NULL DEFAULT '',
    `access_key` VARCHAR(255) NOT NULL DEFAULT '',
    `host_notes` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_online_access_exam_request_id` (`exam_request_id`),
    CONSTRAINT `fk_online_access_event_request`
        FOREIGN KEY (`event_request_id`) REFERENCES `event_requests` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
