#!/bin/sh
set -eu

if [ "${RUN_DB_INIT:-1}" = "1" ]; then
    DB_HOST="${DB_HOST:-127.0.0.1}"
    DB_PORT="${DB_PORT:-3306}"
    DB_NAME="${DB_NAME:-schedule_management_system}"
    DB_USER_NAME="${DB_USER_NAME:-${DB_USER:-root}}"
    DB_USER_PASSWORD="${DB_USER_PASSWORD:-${DB_PASS:-}}"

    case "$DB_NAME" in
        ""|*[!A-Za-z0-9_]*)
            echo "Invalid DB_NAME '$DB_NAME'. Use only letters, digits, and underscores." >&2
            exit 1
            ;;
    esac

    echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
    attempts=0
    until MYSQL_PWD="$DB_USER_PASSWORD" mysqladmin ping \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --user="$DB_USER_NAME" \
        --silent; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge "${DB_INIT_MAX_ATTEMPTS:-60}" ]; then
            echo "MySQL did not become ready in time." >&2
            exit 1
        fi
        sleep 2
    done

    echo "Creating/updating database schema for '$DB_NAME'..."
    sed "s/\${DB_NAME}/$DB_NAME/g" database/schema.sql | MYSQL_PWD="$DB_USER_PASSWORD" mysql \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --user="$DB_USER_NAME"

    for seed_file in \
        database/adminInsert.sql \
        database/teacherInsert.sql \
        database/scheduleManagerInsert.sql; do
        if [ -f "$seed_file" ]; then
            echo "Applying $seed_file..."
            MYSQL_PWD="$DB_USER_PASSWORD" mysql \
                --host="$DB_HOST" \
                --port="$DB_PORT" \
                --user="$DB_USER_NAME" \
                "$DB_NAME" < "$seed_file"
        fi
    done
fi

exec "$@"
