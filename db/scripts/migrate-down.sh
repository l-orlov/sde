#!/usr/bin/env bash
# Revert the last applied migration: run the Down section and remove from schema_migrations.
# Migration format: -- +migrate Up ... -- +migrate Down ... (only Down is run).
# Uses same env as migrate.sh: DB_*, DOCKER_CONTAINER.

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

# Extract SQL after "-- +migrate Down"
extract_down() {
    awk '/^-- \+migrate Down$/ { d=1; next } d' "$1"
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

# First migration (by filename) creates schema_migrations — do not revert it
first_migration=$(ls -1 "${MIGRATIONS_DIR}"/[0-9]*.sql 2>/dev/null | head -1)
first_name=$(basename "$first_migration")

get_last_applied() {
    local exclude="${1:-}"
    if [[ -n "$DOCKER_CONTAINER" ]]; then
        docker exec "$DOCKER_CONTAINER" mariadb -h 127.0.0.1 -P 3306 -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -N -e "SELECT name FROM schema_migrations WHERE name != '$exclude' ORDER BY name DESC LIMIT 1" 2>/dev/null || true
    else
        MYSQL_PWD="$DB_PASSWORD" mariadb -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" -N -e "SELECT name FROM schema_migrations WHERE name != '$exclude' ORDER BY name DESC LIMIT 1" 2>/dev/null || true
    fi
}

last=$(get_last_applied "$first_name")
last="${last//[$'\r\n']/}"

if [[ -z "$last" ]] || [[ "$last" == "$first_name" ]]; then
    echo "Nothing to revert (no applied migrations besides bootstrap)."
    exit 0
fi

migration_file="${MIGRATIONS_DIR}/${last}"
if [[ ! -f "$migration_file" ]]; then
    echo "Migration file not found: $migration_file" >&2
    exit 1
fi

# Check that Down section is non-empty
down_sql=$(extract_down "$migration_file")
if [[ -z "$(echo "$down_sql" | tr -d '[:space:]')" ]]; then
    echo "No Down section in: $last" >&2
    exit 1
fi

echo "Reverting: $last"
echo "$down_sql" | run_stdin
run_sql "DELETE FROM schema_migrations WHERE name = '$last';"
echo "Reverted: $last"
