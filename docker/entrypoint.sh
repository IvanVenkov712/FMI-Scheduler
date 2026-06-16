#!/usr/bin/env bash
set -e

load_env_file() {
    local file="${1:-/var/www/html/.env}"

    if [ ! -f "$file" ]; then
        return
    fi

    while IFS= read -r line || [ -n "$line" ]; do
        line="${line%$'\r'}"

        if [[ "$line" =~ ^[[:space:]]*$ ]] || [[ "$line" =~ ^[[:space:]]*# ]]; then
            continue
        fi

        if [[ "$line" =~ ^[[:space:]]*(export[[:space:]]+)?([A-Za-z_][A-Za-z0-9_]*)[[:space:]]*=(.*)$ ]]; then
            local key="${BASH_REMATCH[2]}"
            local value="${BASH_REMATCH[3]}"

            value="${value#"${value%%[![:space:]]*}"}"
            value="${value%"${value##*[![:space:]]}"}"

            if [ "${#value}" -ge 2 ]; then
                if [[ "${value:0:1}" == "\"" && "${value: -1}" == "\"" ]] || [[ "${value:0:1}" == "'" && "${value: -1}" == "'" ]]; then
                    value="${value:1:${#value}-2}"
                fi
            fi

            if [ -z "${!key:-}" ]; then
                export "$key=$value"
            fi
        fi
    done < "$file"
}

missing_db_vars() {
    local missing=()

    for name in DB_HOST DB_NAME DB_USER_NAME; do
        if [ -z "${!name:-}" ]; then
            missing+=("$name")
        fi
    done

    echo "${missing[*]}"
}

import_db() {
    local missing
    missing="$(missing_db_vars)"

    if [ -n "$missing" ]; then
        echo "Database environment is incomplete (missing: ${missing}), skipping import."
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

load_env_file
import_db
exec "$@"
