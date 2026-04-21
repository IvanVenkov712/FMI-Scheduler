ALTER TABLE routes
    ADD COLUMN walking_duration_minutes SMALLINT UNSIGNED NULL AFTER distance_meters,
    ADD COLUMN car_duration_minutes SMALLINT UNSIGNED NULL AFTER walking_duration_minutes,
    ADD COLUMN public_transport_duration_minutes SMALLINT UNSIGNED NULL AFTER car_duration_minutes;

UPDATE routes
SET walking_duration_minutes = CASE
        WHEN movement_type = 'walking' AND walking_duration_minutes IS NULL THEN duration_minutes
        ELSE walking_duration_minutes
    END,
    car_duration_minutes = CASE
        WHEN movement_type = 'car' AND car_duration_minutes IS NULL THEN duration_minutes
        ELSE car_duration_minutes
    END,
    public_transport_duration_minutes = CASE
        WHEN movement_type = 'public_transport' AND public_transport_duration_minutes IS NULL THEN duration_minutes
        ELSE public_transport_duration_minutes
    END;

ALTER TABLE routes
    DROP COLUMN movement_type,
    DROP COLUMN duration_minutes;
