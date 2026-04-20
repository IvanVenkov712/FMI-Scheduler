INSERT INTO buildings (id, code, name, address, description, floors_count, orientation, schematic_description, status) VALUES
    (1, 'FMI', 'Faculty of Mathematics and Informatics', '5 James Bourchier Blvd, Sofia', 'Primary FMI building with lecture rooms, labs and administrative rooms.', 6, 'east', 'Vertical academic building with multiple classroom floors.', 'active'),
    (2, 'RECTORATE', 'Sofia University Rectorate', '15 Tsar Osvoboditel Blvd, Sofia', 'Central rectorate building used for lectures, exams and administration.', 4, 'north', 'Historic central building with classrooms and halls.', 'active'),
    (3, 'SECOND_BLOCK', 'Second Block', 'Sofia University campus', 'Additional building for classes and exams.', 5, 'west', 'Auxiliary block with mixed capacity rooms.', 'active')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    address = VALUES(address),
    description = VALUES(description),
    floors_count = VALUES(floors_count),
    orientation = VALUES(orientation),
    schematic_description = VALUES(schematic_description),
    status = VALUES(status),
    updated_at = CURRENT_TIMESTAMP;
