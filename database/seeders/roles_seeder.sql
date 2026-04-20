INSERT INTO roles (id, name, slug, description, permissions) VALUES
    (1, 'System Administrator', 'system_admin', 'Full access to buildings, users, schedules, imports and audit data.', JSON_ARRAY('*')),
    (2, 'Schedule Manager', 'schedule_manager', 'Creates schedules, reviews requests and starts automatic allocation.', JSON_ARRAY('schedules.manage', 'events.manage', 'requests.review', 'allocation.run')),
    (3, 'Teacher', 'teacher', 'Creates exam or class requests and reviews assigned rooms.', JSON_ARRAY('requests.create', 'events.view', 'rooms.search')),
    (4, 'Student', 'student', 'Views schedules, rooms, floor plans and routes.', JSON_ARRAY('schedules.view', 'rooms.view', 'routes.view')),
    (5, 'Technical Staff', 'technical_staff', 'Maintains room and resource availability.', JSON_ARRAY('rooms.maintain', 'resources.manage'))
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    permissions = VALUES(permissions),
    updated_at = CURRENT_TIMESTAMP;
