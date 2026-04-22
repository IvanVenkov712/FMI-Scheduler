ALTER TABLE events
    ADD COLUMN required_resources JSON NULL AFTER delivery_mode;
