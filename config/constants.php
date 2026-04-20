<?php

declare(strict_types=1);

defined('APP_NAME') || define('APP_NAME', 'Schedule Management System');
defined('APP_LOCALE') || define('APP_LOCALE', 'bg');
defined('APP_TIMEZONE') || define('APP_TIMEZONE', 'Europe/Sofia');

defined('ROLE_SYSTEM_ADMIN') || define('ROLE_SYSTEM_ADMIN', 'system-admin');
defined('ROLE_SCHEDULE_MANAGER') || define('ROLE_SCHEDULE_MANAGER', 'schedule-manager');
defined('ROLE_TEACHER') || define('ROLE_TEACHER', 'teacher');
defined('ROLE_STUDENT') || define('ROLE_STUDENT', 'student');
defined('ROLE_TECHNICAL_STAFF') || define('ROLE_TECHNICAL_STAFF', 'technical-staff');

defined('ROOM_TYPE_PHYSICAL') || define('ROOM_TYPE_PHYSICAL', 'physical');
defined('ROOM_TYPE_ONLINE') || define('ROOM_TYPE_ONLINE', 'online');
defined('ROOM_TYPE_HYBRID') || define('ROOM_TYPE_HYBRID', 'hybrid');

defined('DELIVERY_MODE_PHYSICAL') || define('DELIVERY_MODE_PHYSICAL', 'physical');
defined('DELIVERY_MODE_ONLINE') || define('DELIVERY_MODE_ONLINE', 'online');
defined('DELIVERY_MODE_HYBRID') || define('DELIVERY_MODE_HYBRID', 'hybrid');

defined('STATUS_ACTIVE') || define('STATUS_ACTIVE', 'active');
defined('STATUS_INACTIVE') || define('STATUS_INACTIVE', 'inactive');
defined('STATUS_AVAILABLE') || define('STATUS_AVAILABLE', 'available');
defined('STATUS_UNAVAILABLE') || define('STATUS_UNAVAILABLE', 'unavailable');
defined('STATUS_PENDING') || define('STATUS_PENDING', 'pending');
defined('STATUS_APPROVED') || define('STATUS_APPROVED', 'approved');
defined('STATUS_REJECTED') || define('STATUS_REJECTED', 'rejected');
defined('STATUS_SCHEDULED') || define('STATUS_SCHEDULED', 'scheduled');
defined('STATUS_CANCELLED') || define('STATUS_CANCELLED', 'cancelled');

defined('EVENT_TYPE_EXAM') || define('EVENT_TYPE_EXAM', 'exam');
defined('EVENT_TYPE_LECTURE') || define('EVENT_TYPE_LECTURE', 'lecture');
defined('EVENT_TYPE_PRACTICE') || define('EVENT_TYPE_PRACTICE', 'practice');
defined('EVENT_TYPE_CONSULTATION') || define('EVENT_TYPE_CONSULTATION', 'consultation');
defined('EVENT_TYPE_OTHER') || define('EVENT_TYPE_OTHER', 'other');

defined('MOVEMENT_WALKING') || define('MOVEMENT_WALKING', 'walking');
defined('MOVEMENT_CAR') || define('MOVEMENT_CAR', 'car');
defined('MOVEMENT_PUBLIC_TRANSPORT') || define('MOVEMENT_PUBLIC_TRANSPORT', 'public_transport');

defined('ORIENTATION_EAST') || define('ORIENTATION_EAST', 'east');
defined('ORIENTATION_WEST') || define('ORIENTATION_WEST', 'west');
defined('ORIENTATION_NORTH') || define('ORIENTATION_NORTH', 'north');
defined('ORIENTATION_SOUTH') || define('ORIENTATION_SOUTH', 'south');

defined('IMPORT_FORMAT_CSV') || define('IMPORT_FORMAT_CSV', 'csv');
defined('IMPORT_FORMAT_JSON') || define('IMPORT_FORMAT_JSON', 'json');
defined('IMPORT_FORMAT_XML') || define('IMPORT_FORMAT_XML', 'xml');
defined('IMPORT_FORMAT_ASCII') || define('IMPORT_FORMAT_ASCII', 'ascii');

return [
    'roles' => [
        ROLE_SYSTEM_ADMIN,
        ROLE_SCHEDULE_MANAGER,
        ROLE_TEACHER,
        ROLE_STUDENT,
        ROLE_TECHNICAL_STAFF,
    ],
    'room_types' => [
        ROOM_TYPE_PHYSICAL,
        ROOM_TYPE_ONLINE,
        ROOM_TYPE_HYBRID,
    ],
    'delivery_modes' => [
        DELIVERY_MODE_PHYSICAL,
        DELIVERY_MODE_ONLINE,
        DELIVERY_MODE_HYBRID,
    ],
    'event_types' => [
        EVENT_TYPE_EXAM,
        EVENT_TYPE_LECTURE,
        EVENT_TYPE_PRACTICE,
        EVENT_TYPE_CONSULTATION,
        EVENT_TYPE_OTHER,
    ],
    'movement_types' => [
        MOVEMENT_WALKING,
        MOVEMENT_CAR,
        MOVEMENT_PUBLIC_TRANSPORT,
    ],
    'orientations' => [
        ORIENTATION_EAST,
        ORIENTATION_WEST,
        ORIENTATION_NORTH,
        ORIENTATION_SOUTH,
    ],
    'statuses' => [
        STATUS_ACTIVE,
        STATUS_INACTIVE,
        STATUS_AVAILABLE,
        STATUS_UNAVAILABLE,
        STATUS_PENDING,
        STATUS_APPROVED,
        STATUS_REJECTED,
        STATUS_SCHEDULED,
        STATUS_CANCELLED,
    ],
    'import_export_formats' => [
        IMPORT_FORMAT_CSV,
        IMPORT_FORMAT_JSON,
        IMPORT_FORMAT_XML,
        IMPORT_FORMAT_ASCII,
        'txt',
    ],
];
