SET @routes_is_bidirectional_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'routes'
      AND COLUMN_NAME = 'is_bidirectional'
);

SET @routes_is_bidirectional_sql := IF(
    @routes_is_bidirectional_exists = 0,
    'ALTER TABLE routes ADD COLUMN is_bidirectional TINYINT(1) NOT NULL DEFAULT 1 AFTER is_accessible',
    'SELECT 1'
);

PREPARE routes_is_bidirectional_stmt FROM @routes_is_bidirectional_sql;
EXECUTE routes_is_bidirectional_stmt;
DEALLOCATE PREPARE routes_is_bidirectional_stmt;
