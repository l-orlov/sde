#!/usr/bin/env bash
# Run migrations in order (by filename). Uses schema_migrations table to run each file only once.
# Migration format: -- +migrate Up ... -- +migrate Down ... (only Up is run).
# Filenames: YYYYMMDDHHMMSS_description.sql (timestamp first for order).
# Requires: DB_*, optional DOCKER_CONTAINER.

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DB_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
MIGRATIONS_DIR="${DB_DIR}/migrations"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3308}"
DB_USER="${DB_USER:-user}"
DB_PASSWORD="${DB_PASSWORD:-password}"
DB_NAME="${DB_NAME:-db}"
DOCKER_CONTAINER="${DOCKER_CONTAINER:-}"

# Extract SQL between "-- +migrate Up" and "-- +migrate Down"
extract_up() {
    awk '/^-- \+migrate Up$/ { u=1; next } /^-- \+migrate Down$/ { u=0; next } u' "$1"
}

run_stdin() {
    if [[ -n "$DOCKER_CONTAINER" ]]; then
        docker exec -i "$DOCKER_CONTAINER" mariadb -h 127.0.0.1 -P 3306 -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < /dev/stdin
    else
        MYSQL_PWD="$DB_PASSWORD" mariadb -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" < /dev/stdin
    fi
}

run_sql() {
    local sql="$1"
    if [[ -n "$DOCKER_CONTAINER" ]]; then
        docker exec -i "$DOCKER_CONTAINER" mariadb -h 127.0.0.1 -P 3306 -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "$sql"
    else
        MYSQL_PWD="$DB_PASSWORD" mariadb -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" -e "$sql"
    fi
}

run_migration_up() {
    extract_up "$1" | run_stdin
}

is_applied() {
    local name="$1"
    local out
    if [[ -n "$DOCKER_CONTAINER" ]]; then
        out=$(docker exec "$DOCKER_CONTAINER" mariadb -h 127.0.0.1 -P 3306 -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -N -e "SELECT 1 FROM schema_migrations WHERE name = '$name' LIMIT 1" 2>/dev/null || true)
    else
        out=$(MYSQL_PWD="$DB_PASSWORD" mariadb -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" -N -e "SELECT 1 FROM schema_migrations WHERE name = '$name' LIMIT 1" 2>/dev/null || true)
    fi
    [[ -n "$out" ]]
}

record_migration() {
    local name="$1"
    run_sql "INSERT INTO schema_migrations (name) VALUES ('$name');"
}

# Sorted by filename (timestamp first)
for f in $(ls -1 "${MIGRATIONS_DIR}"/[0-9]*.sql 2>/dev/null); do
    [[ -f "$f" ]] || continue
    name="$(basename "$f")"
    if is_applied "$name"; then
        echo "Skip (already applied): $name"
        continue
    fi
    echo "Applying: $name"
    if run_migration_up "$f"; then
        record_migration "$name"
    else
        echo "Failed: $name" >&2
        exit 1
    fi
done

echo "Migrations done."
