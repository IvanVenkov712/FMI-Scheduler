#!/usr/bin/env bash
set -e

import_db() {
    if [ -z "${DB_HOST:-}" ] || [ -z "${DB_NAME:-}" ] || [ -z "${DB_USER_NAME:-}" ]; then
        echo "Database environment is incomplete, skipping import."
        return
    fi

    local port="${DB_PORT:-3306}"
    local password_args=()

    if [ -n "${DB_USER_PASSWORD:-}" ]; then
        password_args=("-p${DB_USER_PASSWORD}")
    fi

    echo "Waiting for database at ${DB_HOST}:${port}..."
    for attempt in $(seq 1 30); do
        if mysqladmin ping -h "$DB_HOST" -P "$port" -u "$DB_USER_NAME" "${password_args[@]}" --silent; then
            break
        fi

        if [ "$attempt" -eq 30 ]; then
            echo "Database is not reachable, starting application without import."
            return
        fi

        sleep 2
    done

    echo "Ensuring database schema and seed data..."
    mysql -h "$DB_HOST" -P "$port" -u "$DB_USER_NAME" "${password_args[@]}" "$DB_NAME" < /var/www/html/database/schema.sql
    mysql -h "$DB_HOST" -P "$port" -u "$DB_USER_NAME" "${password_args[@]}" "$DB_NAME" < /var/www/html/database/seed.sql
    echo "Database ready."
}

import_db
exec "$@"
