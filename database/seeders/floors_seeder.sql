INSERT INTO floors (
    id, building_id, number, name, description, plan_data, ascii_plan,
    capacity, width_units, height_units, status
) VALUES
    (1, 1, 0, 'FMI Ground Floor', 'Entrance level with corridors and lecture halls.', '{"grid":{"width":30,"height":12},"zones":[{"code":"ENT","x":1,"y":1,"w":4,"h":3},{"code":"FMI-001","x":6,"y":2,"w":8,"h":5},{"code":"FMI-002","x":16,"y":2,"w":7,"h":5}],"paths":[{"from":"ENT","to":"FMI-001"},{"from":"ENT","to":"FMI-002"}]}', '+-----+-----+-----+\n| E   | 001 | 002 |\n+-----+-----+-----+', 180, 30, 12, 'active'),
    (2, 1, 1, 'FMI First Floor', 'Teaching rooms and computer labs.', '{"grid":{"width":32,"height":12},"zones":[{"code":"FMI-101","x":3,"y":2,"w":8,"h":5},{"code":"FMI-102","x":13,"y":2,"w":6,"h":5},{"code":"STAIRS","x":22,"y":2,"w":4,"h":4}],"paths":[{"from":"STAIRS","to":"FMI-101"},{"from":"STAIRS","to":"FMI-102"}]}', '+-----+-----+-----+\n| 101 | 102 | LAB |\n+-----+-----+-----+', 240, 32, 12, 'active'),
    (3, 2, 1, 'Rectorate First Floor', 'Main academic rooms.', '{"grid":{"width":28,"height":10},"zones":[{"code":"RECT-101","x":4,"y":2,"w":8,"h":5},{"code":"RECT-ENT-1","x":1,"y":1,"w":3,"h":3}],"paths":[{"from":"RECT-ENT-1","to":"RECT-101"}]}', '+-------+-------+\n| R101  | R102  |\n+-------+-------+', 220, 28, 10, 'active'),
    (4, 3, 1, 'Second Block First Floor', 'Mixed classrooms and exam rooms.', '{"grid":{"width":28,"height":10},"zones":[{"code":"SB-201","x":4,"y":2,"w":8,"h":5},{"code":"SB-ENT-1","x":1,"y":1,"w":3,"h":3}],"paths":[{"from":"SB-ENT-1","to":"SB-201"}]}', '+-------+-------+\n| B201  | B202  |\n+-------+-------+', 200, 28, 10, 'active')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    plan_data = VALUES(plan_data),
    ascii_plan = VALUES(ascii_plan),
    capacity = VALUES(capacity),
    width_units = VALUES(width_units),
    height_units = VALUES(height_units),
    status = VALUES(status),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO floor_points (floor_id, code, name, type, x, y, is_accessible) VALUES
    (1, 'FMI-ENT-1', 'Main Entrance', 'entrance', 1, 1, 1),
    (1, 'FMI-STAIRS-1', 'Central Stairs', 'stairs', 8, 2, 1),
    (1, 'FMI-ELEV-1', 'Main Elevator', 'elevator', 10, 2, 1),
    (2, 'FMI-STAIRS-2', 'Central Stairs', 'stairs', 8, 2, 1),
    (2, 'FMI-CORR-1', 'Main Corridor Node', 'corridor', 12, 3, 1),
    (3, 'RECT-ENT-1', 'Rectorate Entrance', 'entrance', 1, 1, 1),
    (4, 'SB-ENT-1', 'Second Block Entrance', 'entrance', 1, 1, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    type = VALUES(type),
    x = VALUES(x),
    y = VALUES(y),
    is_accessible = VALUES(is_accessible),
    updated_at = CURRENT_TIMESTAMP;
