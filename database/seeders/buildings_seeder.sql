INSERT INTO buildings (
    id, code, name, address, description, floors_count, orientation, schematic_description,
    vertical_view_east, vertical_view_west, vertical_view_north, vertical_view_south, status
) VALUES
    (1, 'FMI', 'Faculty of Mathematics and Informatics', '5 James Bourchier Blvd, Sofia', 'Primary FMI building with lecture rooms, labs and administrative rooms.', 6, 'east', 'Vertical academic building with multiple classroom floors.', 'Ground floor entrance, lecture halls above, labs on middle levels.', 'Administrative side with service access and vertical circulation.', 'Front facade with stacked classroom levels and roof equipment.', 'Rear facade with auxiliary rooms and corridor access.', 'active'),
    (2, 'RECTORATE', 'Sofia University Rectorate', '15 Tsar Osvoboditel Blvd, Sofia', 'Central rectorate building used for lectures, exams and administration.', 4, 'north', 'Historic central building with classrooms and halls.', 'Monumental east wing with public entrance and hall access.', 'Service-facing west wing with secondary circulation.', 'Representative north facade with amphitheatre rooms behind it.', 'South courtyard facade with classrooms and offices.', 'active'),
    (3, 'SECOND_BLOCK', 'Second Block', 'Sofia University campus', 'Additional building for classes and exams.', 5, 'west', 'Auxiliary block with mixed capacity rooms.', 'East side with regular classroom stack and one shared stairwell.', 'West side with accessible entrance and wider exam rooms.', 'North side with compact seminar rooms on each level.', 'South side with larger mixed-use teaching spaces.', 'active')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    address = VALUES(address),
    description = VALUES(description),
    floors_count = VALUES(floors_count),
    orientation = VALUES(orientation),
    schematic_description = VALUES(schematic_description),
    vertical_view_east = VALUES(vertical_view_east),
    vertical_view_west = VALUES(vertical_view_west),
    vertical_view_north = VALUES(vertical_view_north),
    vertical_view_south = VALUES(vertical_view_south),
    status = VALUES(status),
    updated_at = CURRENT_TIMESTAMP;
