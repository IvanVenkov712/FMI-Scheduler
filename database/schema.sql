CREATE DATABASE IF NOT EXISTS schedule_management_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE schedule_management_system;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS import_export_jobs;
DROP TABLE IF EXISTS allocation_runs;
DROP TABLE IF EXISTS movement_constraints;
DROP TABLE IF EXISTS allocation_parameters;
DROP TABLE IF EXISTS student_group_members;
DROP TABLE IF EXISTS student_groups;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS route_steps;
DROP TABLE IF EXISTS routes;
DROP TABLE IF EXISTS room_unavailability;
DROP TABLE IF EXISTS schedule_events;
DROP TABLE IF EXISTS schedules;
DROP TABLE IF EXISTS exam_requests;
DROP TABLE IF EXISTS online_sessions;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS resource_requests;
DROP TABLE IF EXISTS room_resources;
DROP TABLE IF EXISTS resources;
DROP TABLE IF EXISTS room_connections;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS room_types;
DROP TABLE IF EXISTS floor_points;
DROP TABLE IF EXISTS floors;
DROP TABLE IF EXISTS buildings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

SOURCE migrations/001_create_users_table.sql;
SOURCE migrations/002_create_roles_table.sql;
SOURCE migrations/003_create_buildings_table.sql;
SOURCE migrations/004_create_floors_table.sql;
SOURCE migrations/005_create_rooms_table.sql;
SOURCE migrations/006_create_resources_table.sql;
SOURCE migrations/007_create_events_table.sql;
SOURCE migrations/008_create_schedules_table.sql;
SOURCE migrations/009_create_routes_table.sql;
SOURCE migrations/010_create_constraints_table.sql;
SOURCE migrations/011_create_audit_logs_table.sql;
SOURCE migrations/012_alter_routes_multi_duration.sql;
SOURCE migrations/013_create_catalog_import_tables.sql;
SOURCE migrations/014_alter_online_sessions_access_fields.sql;
SOURCE migrations/015_alter_exam_requests_online_fields.sql;
SOURCE migrations/016_alter_events_required_resources.sql;
SOURCE migrations/017_alter_routes_directionality.sql;
