#!/bin/bash

import_db() {
    # Import schema if the users table doesn't exist yet
    echo "Checking database..."
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER_NAME" -p"$DB_USER_PASSWORD" "$DB_NAME" -e "DESCRIBE users;" 2>/dev/null

    if [ $? -ne 0 ]; then
        echo "Importing schema..."
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER_NAME" -p"$DB_USER_PASSWORD" "$DB_NAME"  < /database/schema.sql
        echo "Importing seed data..."
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER_NAME" -p"$DB_USER_PASSWORD" "$DB_NAME" < /database/seed.sql
        echo "Database ready."
    else
        echo "Database already initialized, skipping."
    fi
}

# Run DB import in background so Apache starts immediately
import_db &