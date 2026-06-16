SET NAMES utf8mb4;

-- Demo password for all seeded users: Abc123
INSERT IGNORE INTO users (username, first_name, last_name, email, password_hash, `role`, is_active)
VALUES
    ('admin', 'Admin', 'Adminov', 'admin@admin.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'admin', 1),
    ('teacher', 'Teacher', 'Teacherov', 'teacher@teacher.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'teacher', 1),
    ('schedule_manager', 'Schedule', 'Managerov', 'schedule_manager@schedule.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'schedule_manager', 1),
    ('teacher_maria', 'Maria', 'Dimitrova', 'maria.dimitrova@fmi.example.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'teacher', 1),
    ('teacher_georgi', 'Georgi', 'Petrov', 'georgi.petrov@fmi.example.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'teacher', 1),
    ('student_anna', 'Anna', 'Ivanova', 'anna.ivanova@students.fmi.example.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'student', 1),
    ('student_petar', 'Petar', 'Nikolov', 'petar.nikolov@students.fmi.example.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'student', 1),
    ('student_mila', 'Mila', 'Stoyanova', 'mila.stoyanova@students.fmi.example.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'student', 1),
    ('archived_teacher', 'Archived', 'Teacher', 'archived.teacher@fmi.example.com', '$2a$12$V5.5q.3t0cc5cj336fzNaeZrvlGlAOxDSQlyrdjKxgJNFKtkDXb9a', 'teacher', 0);

INSERT IGNORE INTO buildings (name, code, address, campus_area, floor_count, is_active)
VALUES
    ('FMI Main Building', 'FMI-MAIN', '5 James Bourchier Blvd, Sofia', 'Lozenets', 3, 1),
    ('FMI Lab Center', 'FMI-LAB', '125 Tsarigradsko Shose Blvd, Sofia', 'Mladost', 2, 1);

SET @fmi_main_building_id = (SELECT id FROM buildings WHERE code = 'FMI-MAIN' LIMIT 1);
SET @fmi_lab_building_id = (SELECT id FROM buildings WHERE code = 'FMI-LAB' LIMIT 1);

INSERT IGNORE INTO floors (building_id, level, name, ascii_plan, notes)
VALUES
    (@fmi_main_building_id, 0, 'Ground Floor', '[ENT]--[LOBBY]--[A101]\n           |--[A102]\n           |--[STAIRS][ELEV]', 'Main entrance, student services, and two lecture rooms.'),
    (@fmi_main_building_id, 1, 'First Floor', '[B201]--[B202]--[B203]\n   |       |       |\n [STAIRS][ELEV][LOUNGE]', 'Teaching rooms and computer lab.'),
    (@fmi_main_building_id, 2, 'Second Floor', '[C301 AUDITORIUM]--[C302]\n        |          |\n      [EXIT]----[STAIRS][ELEV]', 'Auditorium floor for larger sessions.'),
    (@fmi_lab_building_id, 0, 'Ground Floor', '[L-ENT]--[L101]--[L102]\n    |       |       |\n [STORAGE][STAIRS][ELEV]', 'Specialized labs and exam hall.'),
    (@fmi_lab_building_id, 1, 'First Floor', '[L201 STUDIO]--[L202 PROJECT LAB]\n      |              |\n    [ELEV]--------[STAIRS]', 'Hybrid teaching studio and project workspace.');

SET @floor_main_0_id = (SELECT id FROM floors WHERE building_id = @fmi_main_building_id AND level = 0 LIMIT 1);
SET @floor_main_1_id = (SELECT id FROM floors WHERE building_id = @fmi_main_building_id AND level = 1 LIMIT 1);
SET @floor_main_2_id = (SELECT id FROM floors WHERE building_id = @fmi_main_building_id AND level = 2 LIMIT 1);
SET @floor_lab_0_id = (SELECT id FROM floors WHERE building_id = @fmi_lab_building_id AND level = 0 LIMIT 1);
SET @floor_lab_1_id = (SELECT id FROM floors WHERE building_id = @fmi_lab_building_id AND level = 1 LIMIT 1);

INSERT IGNORE INTO rooms
    (floor_id, name, position_code, room_type, capacity, room_size, is_active, pos_x, pos_y, width, height, is_entrance, is_exit, orientation)
VALUES
    (@floor_main_0_id, 'Main Entrance', 'ENT-G0', 'physical', 30, 'small', 1, 20, 40, 110, 70, 1, 0, 'N'),
    (@floor_main_0_id, 'Lobby', 'LOB-G0', 'physical', 80, 'medium', 1, 160, 40, 170, 90, 0, 0, 'N'),
    (@floor_main_0_id, 'Room A101', 'A101', 'physical', 80, 'large', 1, 380, 35, 150, 100, 0, 0, 'E'),
    (@floor_main_0_id, 'Room A102', 'A102', 'physical', 45, 'medium', 1, 380, 170, 145, 90, 0, 0, 'E'),
    (@floor_main_0_id, 'Main Stairs G0', 'STAIR-G0', 'physical', 25, 'small', 1, 190, 175, 90, 80, 0, 0, 'S'),
    (@floor_main_0_id, 'Main Elevator G0', 'ELEV-G0', 'physical', 8, 'small', 1, 290, 175, 70, 80, 0, 0, 'S'),
    (@floor_main_1_id, 'Room B201', 'B201', 'hybrid', 120, 'large', 1, 40, 45, 170, 105, 0, 0, 'W'),
    (@floor_main_1_id, 'Room B202', 'B202', 'physical', 60, 'medium', 1, 250, 45, 145, 100, 0, 0, 'N'),
    (@floor_main_1_id, 'Computer Lab B203', 'B203', 'hybrid', 36, 'medium', 1, 435, 45, 170, 105, 0, 0, 'E'),
    (@floor_main_1_id, 'Main Stairs F1', 'STAIR-F1', 'physical', 25, 'small', 1, 95, 185, 90, 80, 0, 0, 'S'),
    (@floor_main_1_id, 'Main Elevator F1', 'ELEV-F1', 'physical', 8, 'small', 1, 205, 185, 70, 80, 0, 0, 'S'),
    (@floor_main_2_id, 'Auditorium C301', 'C301', 'hybrid', 180, 'large', 1, 40, 45, 230, 130, 0, 0, 'W'),
    (@floor_main_2_id, 'Seminar Room C302', 'C302', 'physical', 30, 'small', 1, 330, 45, 145, 90, 0, 0, 'E'),
    (@floor_main_2_id, 'Emergency Exit C3', 'EXIT-F2', 'physical', 20, 'small', 1, 330, 180, 120, 75, 0, 1, 'S'),
    (@floor_main_2_id, 'Main Stairs F2', 'STAIR-F2', 'physical', 25, 'small', 1, 95, 205, 90, 80, 0, 0, 'S'),
    (@floor_main_2_id, 'Main Elevator F2', 'ELEV-F2', 'physical', 8, 'small', 1, 205, 205, 70, 80, 0, 0, 'S'),
    (@floor_lab_0_id, 'Lab Entrance', 'L-ENT-G0', 'physical', 30, 'small', 1, 25, 40, 110, 70, 1, 0, 'N'),
    (@floor_lab_0_id, 'Robotics Lab L101', 'L101', 'hybrid', 24, 'medium', 1, 175, 40, 160, 105, 0, 0, 'N'),
    (@floor_lab_0_id, 'Exam Hall L102', 'L102', 'hybrid', 90, 'large', 1, 385, 40, 190, 120, 0, 0, 'E'),
    (@floor_lab_0_id, 'Lab Elevator G0', 'L-ELEV-G0', 'physical', 8, 'small', 1, 190, 185, 70, 80, 0, 0, 'S'),
    (@floor_lab_1_id, 'Online Studio L201', 'L201', 'hybrid', 12, 'small', 1, 55, 45, 150, 100, 0, 0, 'W'),
    (@floor_lab_1_id, 'Project Lab L202', 'L202', 'hybrid', 32, 'medium', 1, 270, 45, 190, 110, 0, 0, 'E'),
    (@floor_lab_1_id, 'Lab Elevator F1', 'L-ELEV-F1', 'physical', 8, 'small', 1, 190, 185, 70, 80, 0, 0, 'S');

SET @room_entrance_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_0_id AND position_code = 'ENT-G0' LIMIT 1);
SET @room_lobby_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_0_id AND position_code = 'LOB-G0' LIMIT 1);
SET @room_a101_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_0_id AND position_code = 'A101' LIMIT 1);
SET @room_a102_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_0_id AND position_code = 'A102' LIMIT 1);
SET @room_stair_g0_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_0_id AND position_code = 'STAIR-G0' LIMIT 1);
SET @room_elev_g0_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_0_id AND position_code = 'ELEV-G0' LIMIT 1);
SET @room_b201_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_1_id AND position_code = 'B201' LIMIT 1);
SET @room_b202_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_1_id AND position_code = 'B202' LIMIT 1);
SET @room_b203_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_1_id AND position_code = 'B203' LIMIT 1);
SET @room_stair_f1_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_1_id AND position_code = 'STAIR-F1' LIMIT 1);
SET @room_elev_f1_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_1_id AND position_code = 'ELEV-F1' LIMIT 1);
SET @room_c301_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_2_id AND position_code = 'C301' LIMIT 1);
SET @room_c302_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_2_id AND position_code = 'C302' LIMIT 1);
SET @room_exit_f2_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_2_id AND position_code = 'EXIT-F2' LIMIT 1);
SET @room_stair_f2_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_2_id AND position_code = 'STAIR-F2' LIMIT 1);
SET @room_elev_f2_id = (SELECT id FROM rooms WHERE floor_id = @floor_main_2_id AND position_code = 'ELEV-F2' LIMIT 1);
SET @room_lab_entrance_id = (SELECT id FROM rooms WHERE floor_id = @floor_lab_0_id AND position_code = 'L-ENT-G0' LIMIT 1);
SET @room_l101_id = (SELECT id FROM rooms WHERE floor_id = @floor_lab_0_id AND position_code = 'L101' LIMIT 1);
SET @room_l102_id = (SELECT id FROM rooms WHERE floor_id = @floor_lab_0_id AND position_code = 'L102' LIMIT 1);
SET @room_lab_elev_g0_id = (SELECT id FROM rooms WHERE floor_id = @floor_lab_0_id AND position_code = 'L-ELEV-G0' LIMIT 1);
SET @room_l201_id = (SELECT id FROM rooms WHERE floor_id = @floor_lab_1_id AND position_code = 'L201' LIMIT 1);
SET @room_l202_id = (SELECT id FROM rooms WHERE floor_id = @floor_lab_1_id AND position_code = 'L202' LIMIT 1);
SET @room_lab_elev_f1_id = (SELECT id FROM rooms WHERE floor_id = @floor_lab_1_id AND position_code = 'L-ELEV-F1' LIMIT 1);

INSERT IGNORE INTO resources (name, description)
VALUES
    ('Projector', 'Ceiling or mobile projector for presentations.'),
    ('Whiteboard', 'Writable board with markers.'),
    ('Video Conference Kit', 'Camera, speaker, and screen-sharing equipment for hybrid sessions.'),
    ('Computer Workstation', 'Student workstation with required development tools.'),
    ('Exam Scanner', 'Scanner for exam sheets and attendance materials.'),
    ('Microphone', 'Handheld or fixed microphone for larger rooms.'),
    ('Lab Kit', 'Reusable hardware and experiment kit for practical classes.'),
    ('Document Camera', 'Camera for showing paper materials during a lecture.');

SET @resource_projector_id = (SELECT id FROM resources WHERE name = 'Projector' LIMIT 1);
SET @resource_whiteboard_id = (SELECT id FROM resources WHERE name = 'Whiteboard' LIMIT 1);
SET @resource_video_id = (SELECT id FROM resources WHERE name = 'Video Conference Kit' LIMIT 1);
SET @resource_computer_id = (SELECT id FROM resources WHERE name = 'Computer Workstation' LIMIT 1);
SET @resource_exam_scanner_id = (SELECT id FROM resources WHERE name = 'Exam Scanner' LIMIT 1);
SET @resource_microphone_id = (SELECT id FROM resources WHERE name = 'Microphone' LIMIT 1);
SET @resource_lab_kit_id = (SELECT id FROM resources WHERE name = 'Lab Kit' LIMIT 1);
SET @resource_document_camera_id = (SELECT id FROM resources WHERE name = 'Document Camera' LIMIT 1);

INSERT IGNORE INTO room_resources (resource_id, room_id, resource_quantity)
VALUES
    (@resource_projector_id, @room_a101_id, 1),
    (@resource_whiteboard_id, @room_a101_id, 2),
    (@resource_microphone_id, @room_a101_id, 1),
    (@resource_projector_id, @room_a102_id, 1),
    (@resource_whiteboard_id, @room_a102_id, 1),
    (@resource_projector_id, @room_b201_id, 1),
    (@resource_whiteboard_id, @room_b201_id, 2),
    (@resource_video_id, @room_b201_id, 1),
    (@resource_microphone_id, @room_b201_id, 2),
    (@resource_document_camera_id, @room_b201_id, 1),
    (@resource_projector_id, @room_b202_id, 1),
    (@resource_whiteboard_id, @room_b202_id, 1),
    (@resource_computer_id, @room_b203_id, 36),
    (@resource_projector_id, @room_b203_id, 1),
    (@resource_whiteboard_id, @room_b203_id, 1),
    (@resource_video_id, @room_b203_id, 1),
    (@resource_projector_id, @room_c301_id, 2),
    (@resource_whiteboard_id, @room_c301_id, 2),
    (@resource_video_id, @room_c301_id, 1),
    (@resource_microphone_id, @room_c301_id, 4),
    (@resource_document_camera_id, @room_c301_id, 1),
    (@resource_whiteboard_id, @room_c302_id, 1),
    (@resource_projector_id, @room_l101_id, 1),
    (@resource_whiteboard_id, @room_l101_id, 1),
    (@resource_video_id, @room_l101_id, 1),
    (@resource_lab_kit_id, @room_l101_id, 10),
    (@resource_projector_id, @room_l102_id, 1),
    (@resource_whiteboard_id, @room_l102_id, 2),
    (@resource_video_id, @room_l102_id, 1),
    (@resource_exam_scanner_id, @room_l102_id, 2),
    (@resource_microphone_id, @room_l102_id, 2),
    (@resource_video_id, @room_l201_id, 2),
    (@resource_microphone_id, @room_l201_id, 2),
    (@resource_document_camera_id, @room_l201_id, 1),
    (@resource_computer_id, @room_l201_id, 4),
    (@resource_computer_id, @room_l202_id, 32),
    (@resource_lab_kit_id, @room_l202_id, 8),
    (@resource_projector_id, @room_l202_id, 1),
    (@resource_whiteboard_id, @room_l202_id, 1),
    (@resource_video_id, @room_l202_id, 1);

INSERT IGNORE INTO route_segments (from_room_id, to_room_id, distance, corridor_capacity, is_stairs, is_elevator, is_available)
VALUES
    (@room_entrance_id, @room_lobby_id, 8.00, 120, 0, 0, 1),
    (@room_lobby_id, @room_entrance_id, 8.00, 120, 0, 0, 1),
    (@room_lobby_id, @room_a101_id, 12.00, 80, 0, 0, 1),
    (@room_a101_id, @room_lobby_id, 12.00, 80, 0, 0, 1),
    (@room_lobby_id, @room_a102_id, 15.00, 70, 0, 0, 1),
    (@room_a102_id, @room_lobby_id, 15.00, 70, 0, 0, 1),
    (@room_lobby_id, @room_stair_g0_id, 10.00, 50, 0, 0, 1),
    (@room_stair_g0_id, @room_lobby_id, 10.00, 50, 0, 0, 1),
    (@room_lobby_id, @room_elev_g0_id, 11.00, 20, 0, 0, 1),
    (@room_elev_g0_id, @room_lobby_id, 11.00, 20, 0, 0, 1),
    (@room_stair_g0_id, @room_stair_f1_id, 16.00, 35, 1, 0, 1),
    (@room_stair_f1_id, @room_stair_g0_id, 16.00, 35, 1, 0, 1),
    (@room_stair_f1_id, @room_stair_f2_id, 16.00, 35, 1, 0, 1),
    (@room_stair_f2_id, @room_stair_f1_id, 16.00, 35, 1, 0, 1),
    (@room_elev_g0_id, @room_elev_f1_id, 14.00, 8, 0, 1, 1),
    (@room_elev_f1_id, @room_elev_g0_id, 14.00, 8, 0, 1, 1),
    (@room_elev_f1_id, @room_elev_f2_id, 14.00, 8, 0, 1, 1),
    (@room_elev_f2_id, @room_elev_f1_id, 14.00, 8, 0, 1, 1),
    (@room_stair_f1_id, @room_b201_id, 9.00, 60, 0, 0, 1),
    (@room_b201_id, @room_stair_f1_id, 9.00, 60, 0, 0, 1),
    (@room_b201_id, @room_b202_id, 12.00, 65, 0, 0, 1),
    (@room_b202_id, @room_b201_id, 12.00, 65, 0, 0, 1),
    (@room_b202_id, @room_b203_id, 14.00, 65, 0, 0, 1),
    (@room_b203_id, @room_b202_id, 14.00, 65, 0, 0, 1),
    (@room_b201_id, @room_elev_f1_id, 10.00, 35, 0, 0, 1),
    (@room_elev_f1_id, @room_b201_id, 10.00, 35, 0, 0, 1),
    (@room_stair_f2_id, @room_c301_id, 10.00, 70, 0, 0, 1),
    (@room_c301_id, @room_stair_f2_id, 10.00, 70, 0, 0, 1),
    (@room_c301_id, @room_c302_id, 18.00, 75, 0, 0, 1),
    (@room_c302_id, @room_c301_id, 18.00, 75, 0, 0, 1),
    (@room_c302_id, @room_exit_f2_id, 9.00, 60, 0, 0, 1),
    (@room_exit_f2_id, @room_c302_id, 9.00, 60, 0, 0, 1),
    (@room_c301_id, @room_elev_f2_id, 12.00, 35, 0, 0, 1),
    (@room_elev_f2_id, @room_c301_id, 12.00, 35, 0, 0, 1),
    (@room_lab_entrance_id, @room_l101_id, 10.00, 70, 0, 0, 1),
    (@room_l101_id, @room_lab_entrance_id, 10.00, 70, 0, 0, 1),
    (@room_l101_id, @room_l102_id, 13.00, 65, 0, 0, 1),
    (@room_l102_id, @room_l101_id, 13.00, 65, 0, 0, 1),
    (@room_l101_id, @room_lab_elev_g0_id, 9.00, 25, 0, 0, 1),
    (@room_lab_elev_g0_id, @room_l101_id, 9.00, 25, 0, 0, 1),
    (@room_lab_elev_g0_id, @room_lab_elev_f1_id, 12.00, 8, 0, 1, 1),
    (@room_lab_elev_f1_id, @room_lab_elev_g0_id, 12.00, 8, 0, 1, 1),
    (@room_lab_elev_f1_id, @room_l201_id, 8.00, 25, 0, 0, 1),
    (@room_l201_id, @room_lab_elev_f1_id, 8.00, 25, 0, 0, 1),
    (@room_l201_id, @room_l202_id, 15.00, 60, 0, 0, 1),
    (@room_l202_id, @room_l201_id, 15.00, 60, 0, 0, 1);

INSERT INTO online_access (platform, meeting_link, access_key, host_notes)
SELECT 'Microsoft Teams', 'https://meet.example.com/fmi/data-structures-exam', 'DS-2026', 'Hybrid exam room is monitored by the teaching assistant.'
WHERE NOT EXISTS (
    SELECT 1 FROM online_access WHERE meeting_link = 'https://meet.example.com/fmi/data-structures-exam'
);

INSERT INTO online_access (platform, meeting_link, access_key, host_notes)
SELECT 'Zoom', 'https://meet.example.com/fmi/database-consultation', 'DB-CONSULT', 'Waiting room enabled for individual questions.'
WHERE NOT EXISTS (
    SELECT 1 FROM online_access WHERE meeting_link = 'https://meet.example.com/fmi/database-consultation'
);

INSERT INTO online_access (platform, meeting_link, access_key, host_notes)
SELECT 'Google Meet', 'https://meet.example.com/fmi/software-engineering-seminar', 'SEMINAR-26', 'Recording is enabled for absent students.'
WHERE NOT EXISTS (
    SELECT 1 FROM online_access WHERE meeting_link = 'https://meet.example.com/fmi/software-engineering-seminar'
);

INSERT INTO online_access (platform, meeting_link, access_key, host_notes)
SELECT 'Microsoft Teams', 'https://meet.example.com/fmi/ai-review-session', 'AI-REVIEW', 'Remote-only review session before the project defense.'
WHERE NOT EXISTS (
    SELECT 1 FROM online_access WHERE meeting_link = 'https://meet.example.com/fmi/ai-review-session'
);

SET @online_ds_exam_id = (SELECT id FROM online_access WHERE meeting_link = 'https://meet.example.com/fmi/data-structures-exam' LIMIT 1);
SET @online_db_consultation_id = (SELECT id FROM online_access WHERE meeting_link = 'https://meet.example.com/fmi/database-consultation' LIMIT 1);
SET @online_seminar_id = (SELECT id FROM online_access WHERE meeting_link = 'https://meet.example.com/fmi/software-engineering-seminar' LIMIT 1);
SET @online_ai_review_id = (SELECT id FROM online_access WHERE meeting_link = 'https://meet.example.com/fmi/ai-review-session' LIMIT 1);

SET @teacher_id = (SELECT id FROM users WHERE username = 'teacher' LIMIT 1);
SET @teacher_maria_id = (SELECT id FROM users WHERE username = 'teacher_maria' LIMIT 1);
SET @teacher_georgi_id = (SELECT id FROM users WHERE username = 'teacher_georgi' LIMIT 1);

INSERT INTO events
    (room_id, online_access_id, requestor_id, name, type, start_time, end_time, delivery_mode, subject_name, student_count, status)
SELECT @room_a101_id, NULL, @teacher_id, 'Linear Algebra Lecture', 'lecture', '2026-06-22 09:00:00', '2026-06-22 10:30:00', 'physical', 'Linear Algebra', 75, 'approved'
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE name = 'Linear Algebra Lecture' AND start_time = '2026-06-22 09:00:00' AND requestor_id = @teacher_id
);

INSERT INTO events
    (room_id, online_access_id, requestor_id, name, type, start_time, end_time, delivery_mode, subject_name, student_count, status)
SELECT @room_l102_id, @online_ds_exam_id, @teacher_maria_id, 'Data Structures Hybrid Exam', 'exam', '2026-06-22 11:00:00', '2026-06-22 13:00:00', 'hybrid', 'Data Structures', 85, 'pending'
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE name = 'Data Structures Hybrid Exam' AND start_time = '2026-06-22 11:00:00' AND requestor_id = @teacher_maria_id
);

INSERT INTO events
    (room_id, online_access_id, requestor_id, name, type, start_time, end_time, delivery_mode, subject_name, student_count, status)
SELECT @room_b203_id, NULL, @teacher_georgi_id, 'Algorithms Practice', 'practice', '2026-06-23 11:00:00', '2026-06-23 13:00:00', 'physical', 'Algorithms', 32, 'approved'
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE name = 'Algorithms Practice' AND start_time = '2026-06-23 11:00:00' AND requestor_id = @teacher_georgi_id
);

INSERT INTO events
    (room_id, online_access_id, requestor_id, name, type, start_time, end_time, delivery_mode, subject_name, student_count, status)
SELECT NULL, @online_db_consultation_id, @teacher_maria_id, 'Database Systems Consultation', 'consultation', '2026-06-24 16:00:00', '2026-06-24 17:00:00', 'online', 'Database Systems', 25, 'approved'
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE name = 'Database Systems Consultation' AND start_time = '2026-06-24 16:00:00' AND requestor_id = @teacher_maria_id
);

INSERT INTO events
    (room_id, online_access_id, requestor_id, name, type, start_time, end_time, delivery_mode, subject_name, student_count, status)
SELECT @room_l202_id, NULL, @teacher_georgi_id, 'Operating Systems Lab', 'practice', '2026-06-25 14:00:00', '2026-06-25 16:00:00', 'physical', 'Operating Systems', 28, 'pending'
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE name = 'Operating Systems Lab' AND start_time = '2026-06-25 14:00:00' AND requestor_id = @teacher_georgi_id
);

INSERT INTO events
    (room_id, online_access_id, requestor_id, name, type, start_time, end_time, delivery_mode, subject_name, student_count, status)
SELECT @room_c301_id, @online_seminar_id, @teacher_id, 'Software Engineering Seminar', 'lecture', '2026-06-26 10:00:00', '2026-06-26 12:00:00', 'hybrid', 'Software Engineering', 150, 'approved'
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE name = 'Software Engineering Seminar' AND start_time = '2026-06-26 10:00:00' AND requestor_id = @teacher_id
);

INSERT INTO events
    (room_id, online_access_id, requestor_id, name, type, start_time, end_time, delivery_mode, subject_name, student_count, status)
SELECT @room_c301_id, NULL, @teacher_maria_id, 'Discrete Mathematics Exam', 'exam', '2026-06-27 09:00:00', '2026-06-27 12:00:00', 'physical', 'Discrete Mathematics', 160, 'approved'
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE name = 'Discrete Mathematics Exam' AND start_time = '2026-06-27 09:00:00' AND requestor_id = @teacher_maria_id
);

INSERT INTO events
    (room_id, online_access_id, requestor_id, name, type, start_time, end_time, delivery_mode, subject_name, student_count, status)
SELECT @room_b202_id, NULL, @teacher_georgi_id, 'Computer Networks Lecture', 'lecture', '2026-06-26 13:00:00', '2026-06-26 14:30:00', 'physical', 'Computer Networks', 55, 'cancelled'
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE name = 'Computer Networks Lecture' AND start_time = '2026-06-26 13:00:00' AND requestor_id = @teacher_georgi_id
);

INSERT INTO events
    (room_id, online_access_id, requestor_id, name, type, start_time, end_time, delivery_mode, subject_name, student_count, status)
SELECT NULL, @online_ai_review_id, @teacher_id, 'AI Project Review Session', 'consultation', '2026-06-29 18:00:00', '2026-06-29 19:00:00', 'online', 'Artificial Intelligence', 45, 'pending'
WHERE NOT EXISTS (
    SELECT 1 FROM events WHERE name = 'AI Project Review Session' AND start_time = '2026-06-29 18:00:00' AND requestor_id = @teacher_id
);

SET @event_linear_algebra_id = (SELECT id FROM events WHERE name = 'Linear Algebra Lecture' AND start_time = '2026-06-22 09:00:00' LIMIT 1);
SET @event_ds_exam_id = (SELECT id FROM events WHERE name = 'Data Structures Hybrid Exam' AND start_time = '2026-06-22 11:00:00' LIMIT 1);
SET @event_algorithms_id = (SELECT id FROM events WHERE name = 'Algorithms Practice' AND start_time = '2026-06-23 11:00:00' LIMIT 1);
SET @event_db_consultation_id = (SELECT id FROM events WHERE name = 'Database Systems Consultation' AND start_time = '2026-06-24 16:00:00' LIMIT 1);
SET @event_os_lab_id = (SELECT id FROM events WHERE name = 'Operating Systems Lab' AND start_time = '2026-06-25 14:00:00' LIMIT 1);
SET @event_seminar_id = (SELECT id FROM events WHERE name = 'Software Engineering Seminar' AND start_time = '2026-06-26 10:00:00' LIMIT 1);
SET @event_discrete_exam_id = (SELECT id FROM events WHERE name = 'Discrete Mathematics Exam' AND start_time = '2026-06-27 09:00:00' LIMIT 1);
SET @event_networks_id = (SELECT id FROM events WHERE name = 'Computer Networks Lecture' AND start_time = '2026-06-26 13:00:00' LIMIT 1);
SET @event_ai_review_id = (SELECT id FROM events WHERE name = 'AI Project Review Session' AND start_time = '2026-06-29 18:00:00' LIMIT 1);

INSERT IGNORE INTO resource_requirements (event_id, resource_id, required_quantity, notes)
VALUES
    (@event_linear_algebra_id, @resource_projector_id, 1, 'Slides for lecture examples.'),
    (@event_linear_algebra_id, @resource_whiteboard_id, 1, 'Problem solving on board.'),
    (@event_ds_exam_id, @resource_projector_id, 1, 'Exam instructions.'),
    (@event_ds_exam_id, @resource_exam_scanner_id, 1, 'Scan exam materials after collection.'),
    (@event_ds_exam_id, @resource_video_id, 1, 'Hybrid monitoring and remote participation.'),
    (@event_algorithms_id, @resource_computer_id, 32, 'One workstation per student.'),
    (@event_algorithms_id, @resource_whiteboard_id, 1, 'Algorithm walkthroughs.'),
    (@event_os_lab_id, @resource_computer_id, 28, 'Linux lab image required.'),
    (@event_os_lab_id, @resource_lab_kit_id, 6, 'Peripheral devices for group tasks.'),
    (@event_seminar_id, @resource_projector_id, 1, 'Talk slides.'),
    (@event_seminar_id, @resource_video_id, 1, 'Streaming to remote students.'),
    (@event_seminar_id, @resource_microphone_id, 2, 'Audience Q&A.'),
    (@event_discrete_exam_id, @resource_projector_id, 1, 'Start and end notices.'),
    (@event_discrete_exam_id, @resource_microphone_id, 1, 'Exam announcements.'),
    (@event_networks_id, @resource_projector_id, 1, 'Cancelled session kept for status testing.');

INSERT INTO schedules (name, description, start_date, end_date)
SELECT 'Week 4 Teaching Schedule', 'Lectures, practices, and seminars for the last teaching week of June 2026.', '2026-06-22', '2026-06-28'
WHERE NOT EXISTS (
    SELECT 1 FROM schedules WHERE name = 'Week 4 Teaching Schedule' AND start_date = '2026-06-22' AND end_date = '2026-06-28'
);

INSERT INTO schedules (name, description, start_date, end_date)
SELECT 'Summer Exam Session 2026', 'Seeded exam schedule with pending and approved requests.', '2026-06-22', '2026-06-30'
WHERE NOT EXISTS (
    SELECT 1 FROM schedules WHERE name = 'Summer Exam Session 2026' AND start_date = '2026-06-22' AND end_date = '2026-06-30'
);

INSERT INTO schedules (name, description, start_date, end_date)
SELECT 'Online Consultations June 2026', 'Remote consultations and review sessions for students.', '2026-06-24', '2026-06-30'
WHERE NOT EXISTS (
    SELECT 1 FROM schedules WHERE name = 'Online Consultations June 2026' AND start_date = '2026-06-24' AND end_date = '2026-06-30'
);

SET @schedule_week4_id = (SELECT id FROM schedules WHERE name = 'Week 4 Teaching Schedule' AND start_date = '2026-06-22' LIMIT 1);
SET @schedule_exam_id = (SELECT id FROM schedules WHERE name = 'Summer Exam Session 2026' AND start_date = '2026-06-22' LIMIT 1);
SET @schedule_online_id = (SELECT id FROM schedules WHERE name = 'Online Consultations June 2026' AND start_date = '2026-06-24' LIMIT 1);

INSERT IGNORE INTO schedule_events (schedule_id, event_id)
VALUES
    (@schedule_week4_id, @event_linear_algebra_id),
    (@schedule_week4_id, @event_algorithms_id),
    (@schedule_week4_id, @event_os_lab_id),
    (@schedule_week4_id, @event_seminar_id),
    (@schedule_week4_id, @event_networks_id),
    (@schedule_exam_id, @event_ds_exam_id),
    (@schedule_exam_id, @event_discrete_exam_id),
    (@schedule_online_id, @event_db_consultation_id),
    (@schedule_online_id, @event_ai_review_id);

SET @segment_lobby_a101_id = (
    SELECT id FROM route_segments
    WHERE from_room_id = @room_lobby_id AND to_room_id = @room_a101_id
    LIMIT 1
);
SET @segment_b201_b202_id = (
    SELECT id FROM route_segments
    WHERE from_room_id = @room_b201_id AND to_room_id = @room_b202_id
    LIMIT 1
);
SET @segment_lab_l101_l102_id = (
    SELECT id FROM route_segments
    WHERE from_room_id = @room_l101_id AND to_room_id = @room_l102_id
    LIMIT 1
);

INSERT INTO movement_constraints (route_segment_id, constraint_type, max_capacity, start_time, end_time, notes)
SELECT @segment_lobby_a101_id, 'capacity_limit', 40, '2026-06-22 08:30:00', '2026-06-22 11:00:00', 'Queue control during the morning lecture.'
WHERE @segment_lobby_a101_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM movement_constraints
    WHERE route_segment_id = @segment_lobby_a101_id
      AND constraint_type = 'capacity_limit'
      AND start_time = '2026-06-22 08:30:00'
);

INSERT INTO movement_constraints (route_segment_id, constraint_type, max_capacity, start_time, end_time, notes)
SELECT @segment_b201_b202_id, 'maintenance', 0, '2026-06-25 08:00:00', '2026-06-25 12:00:00', 'Short maintenance window for corridor lighting.'
WHERE @segment_b201_b202_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM movement_constraints
    WHERE route_segment_id = @segment_b201_b202_id
      AND constraint_type = 'maintenance'
      AND start_time = '2026-06-25 08:00:00'
);

INSERT INTO movement_constraints (route_segment_id, constraint_type, max_capacity, start_time, end_time, notes)
SELECT @segment_lab_l101_l102_id, 'reduced_capacity', 25, '2026-06-22 10:30:00', '2026-06-22 13:30:00', 'Exam hall queue management.'
WHERE @segment_lab_l101_l102_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM movement_constraints
    WHERE route_segment_id = @segment_lab_l101_l102_id
      AND constraint_type = 'reduced_capacity'
      AND start_time = '2026-06-22 10:30:00'
);
