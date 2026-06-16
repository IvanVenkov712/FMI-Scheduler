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

# Start Apache
exec apache2-foregroundAPP_NAME="Table Annotator"
APP_ENV=local
APP_URL=http://localhost:8080
DB_HOST=db
DB_PORT=3306
DB_NAME=table_annotator
DB_USER_NAME=app
DB_USER_PASSWORD=app_password