INSERT INTO floors (id, building_id, number, name, description, ascii_plan, capacity, width_units, height_units, status) VALUES
    (1, 1, 0, 'FMI Ground Floor', 'Entrance level with corridors and lecture halls.', '+-----+-----+-----+\n| E   | 001 | 002 |\n+-----+-----+-----+', 180, 30, 12, 'active'),
    (2, 1, 1, 'FMI First Floor', 'Teaching rooms and computer labs.', '+-----+-----+-----+\n| 101 | 102 | LAB |\n+-----+-----+-----+', 240, 32, 12, 'active'),
    (3, 2, 1, 'Rectorate First Floor', 'Main academic rooms.', '+-------+-------+\n| R101  | R102  |\n+-------+-------+', 220, 28, 10, 'active'),
    (4, 3, 1, 'Second Block First Floor', 'Mixed classrooms and exam rooms.', '+-------+-------+\n| B201  | B202  |\n+-------+-------+', 200, 28, 10, 'active')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    ascii_plan = VALUES(ascii_plan),
    capacity = VALUES(capacity),
    width_units = VALUES(width_units),
    height_units = VALUES(height_units),
    status = VALUES(status),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO floor_points (floor_id, code, name, type, x, y, is_accessible) VALUES
    (1, 'FMI-ENT-1', 'Main Entrance', 'entrance', 1, 1, 1),
    (1, 'FMI-STAIRS-1', 'Central Stairs', 'stairs', 8, 2, 1),
    (2, 'FMI-STAIRS-2', 'Central Stairs', 'stairs', 8, 2, 1),
    (3, 'RECT-ENT-1', 'Rectorate Entrance', 'entrance', 1, 1, 1),
    (4, 'SB-ENT-1', 'Second Block Entrance', 'entrance', 1, 1, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    type = VALUES(type),
    x = VALUES(x),
    y = VALUES(y),
    is_accessible = VALUES(is_accessible),
    updated_at = CURRENT_TIMESTAMP;
