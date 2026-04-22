INSERT INTO room_types (id, name, slug, description, is_physical, supports_online) VALUES
    (1, 'Physical Room', 'physical', 'Standard physical classroom or hall.', 1, 0),
    (2, 'Online Room', 'online', 'Virtual session hosted in an online platform.', 0, 1),
    (3, 'Hybrid Room', 'hybrid', 'Physical room with online participation support.', 1, 1),
    (4, 'Computer Lab', 'computer_lab', 'Physical computer laboratory.', 1, 0)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    is_physical = VALUES(is_physical),
    supports_online = VALUES(supports_online),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO rooms (id, floor_id, room_type_id, code, name, description, capacity, covid_capacity, x, y, width, height, online_url, status) VALUES
    (1, 1, 1, 'FMI-001', 'FMI 001', 'Ground floor lecture hall.', 120, 60, 6, 2, 8, 5, NULL, 'available'),
    (2, 1, 3, 'FMI-002', 'FMI 002', 'Hybrid lecture hall with projector and camera.', 80, 40, 16, 2, 7, 5, NULL, 'available'),
    (3, 2, 4, 'FMI-101', 'FMI 101 Computer Lab', 'Computer lab for exercises and practical exams.', 36, 18, 3, 2, 8, 5, NULL, 'available'),
    (4, 2, 1, 'FMI-102', 'FMI 102', 'Seminar room.', 45, 22, 13, 2, 6, 5, NULL, 'available'),
    (5, NULL, 2, 'ONLINE-ZOOM-1', 'Online Zoom Room 1', 'Reusable online room for remote exams and consultations.', 300, 300, NULL, NULL, NULL, NULL, 'https://example.test/zoom/online-1', 'available'),
    (6, 3, 1, 'RECT-101', 'Rectorate 101', 'Rectorate lecture room.', 100, 50, 4, 2, 8, 5, NULL, 'available'),
    (7, 4, 1, 'SB-201', 'Second Block 201', 'Second block classroom.', 90, 45, 4, 2, 8, 5, NULL, 'available')
ON DUPLICATE KEY UPDATE
    floor_id = VALUES(floor_id),
    room_type_id = VALUES(room_type_id),
    name = VALUES(name),
    description = VALUES(description),
    capacity = VALUES(capacity),
    covid_capacity = VALUES(covid_capacity),
    x = VALUES(x),
    y = VALUES(y),
    width = VALUES(width),
    height = VALUES(height),
    online_url = VALUES(online_url),
    status = VALUES(status),
    updated_at = CURRENT_TIMESTAMP;

UPDATE rooms
SET access_key = CASE id
    WHEN 2 THEN 'FMI-002-HYBRID'
    WHEN 5 THEN 'ZOOM-ROOM-1-ACCESS'
    ELSE access_key
END
WHERE id IN (2, 5);

INSERT INTO resources (id, name, slug, description, unit, is_consumable) VALUES
    (1, 'Projector', 'projector', 'Presentation projector.', 'pcs', 0),
    (2, 'Second Projector', 'second_projector', 'Additional projector for exams or large rooms.', 'pcs', 0),
    (3, 'Camera', 'camera', 'Camera for hybrid teaching.', 'pcs', 0),
    (4, 'Graphic Tablet', 'graphic_tablet', 'Tablet for digital writing.', 'pcs', 0),
    (5, 'Markers', 'markers', 'Whiteboard markers.', 'sets', 1),
    (6, 'Paper Sheets', 'paper_sheets', 'Paper sheets for exams.', 'pcs', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    unit = VALUES(unit),
    is_consumable = VALUES(is_consumable),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO room_resources (room_id, resource_id, quantity, status) VALUES
    (1, 1, 1, 'available'),
    (1, 5, 2, 'available'),
    (2, 1, 1, 'available'),
    (2, 2, 1, 'available'),
    (2, 3, 1, 'available'),
    (3, 1, 1, 'available'),
    (3, 4, 2, 'available'),
    (4, 1, 1, 'available'),
    (6, 1, 1, 'available'),
    (7, 1, 1, 'available')
ON DUPLICATE KEY UPDATE
    quantity = VALUES(quantity),
    status = VALUES(status),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO room_connections (from_room_id, to_room_id, connection_type, is_bidirectional, walking_minutes) VALUES
    (1, 2, 'corridor', 1, 1),
    (1, 3, 'stairs', 1, 2),
    (2, 4, 'corridor', 1, 1),
    (3, 4, 'corridor', 1, 1),
    (6, 7, 'outdoor', 1, 12)
ON DUPLICATE KEY UPDATE
    is_bidirectional = VALUES(is_bidirectional),
    walking_minutes = VALUES(walking_minutes);
