# Local DB and migrations (run from db/ or via root: make -C db <target>)
# Defaults match docker/docker-compose.yml
DB_HOST     ?= 127.0.0.1
DB_PORT     ?= 3308
DB_USER     ?= user
DB_PASSWORD ?= password
DB_NAME     ?= db
DOCKER_COMPOSE  := docker compose -f docker/docker-compose.yml
CONTAINER_NAME  := sde_mariadb

.PHONY: up-db down-db clean rebuild migrate-up migrate-down schema-dump new-migration fill-testdata db-wait db-shell help

help:
	@echo "DB (run from db/ or: make -C db <target>):"
	@echo "  make up-db         - Start MariaDB (docker compose up -d)"
	@echo "  make down-db       - Stop MariaDB"
	@echo "  make clean         - Stop MariaDB and remove volume (full reset, no data left)"
	@echo "  make rebuild       - clean + up-db + db-wait + migrate-up + fill-testdata"
	@echo "  make db-wait       - Wait until MariaDB accepts connections"
	@echo "  make db-shell      - Open MariaDB shell in container"
	@echo "Migrations:"
	@echo "  make migrate-up    - Apply pending migrations"
	@echo "  make migrate-down  - Revert last applied migration"
	@echo "  make schema-dump   - Dump current schema to schema.sql"
	@echo "  make new-migration [NAME=description] - Create new migration (NAME optional)"
	@echo "Test data:"
	@echo "  make fill-testdata - Load test users, companies, products (db/testdata/fill_test_data.sql)"
	@echo ""
	@echo "Override: DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME"

up-db:
	$(DOCKER_COMPOSE) up -d

down-db:
	$(DOCKER_COMPOSE) down

clean:
	$(DOCKER_COMPOSE) down -v
	@echo "DB and volume removed. Next: make up-db && make db-wait && make migrate-up"

rebuild: clean
	@$(MAKE) up-db
	@$(MAKE) db-wait
	@$(MAKE) migrate-up
	@$(MAKE) fill-testdata
	@echo "Rebuild done: DB reset, migrations applied, test data loaded."

db-wait:
	@echo "Waiting for MariaDB..."
	@for i in 1 2 3 4 5 6 7 8 9 10; do \
		if docker exec $(CONTAINER_NAME) mariadb -h 127.0.0.1 -P 3306 -u $(DB_USER) -p$(DB_PASSWORD) -e "SELECT 1" 2>/dev/null; then \
			echo "MariaDB is ready."; \
			exit 0; \
		fi; \
		sleep 2; \
	done; \
	echo "Timeout waiting for MariaDB."; exit 1

migrate-up:
	DOCKER_CONTAINER=$(CONTAINER_NAME) DB_USER=$(DB_USER) DB_PASSWORD=$(DB_PASSWORD) DB_NAME=$(DB_NAME) bash scripts/migrate.sh
	@$(MAKE) schema-dump

migrate-down:
	DOCKER_CONTAINER=$(CONTAINER_NAME) DB_USER=$(DB_USER) DB_PASSWORD=$(DB_PASSWORD) DB_NAME=$(DB_NAME) bash scripts/migrate-down.sh
	@$(MAKE) schema-dump

schema-dump:
	docker exec $(CONTAINER_NAME) mariadb-dump -h 127.0.0.1 -P 3306 -u $(DB_USER) -p$(DB_PASSWORD) --no-data --single-transaction --skip-comments $(DB_NAME) \
	| sed -e '/^\s*\/\*!/d' -e '/^\s*\/\*M!/d' \
	> schema.sql
	@echo "Schema written to schema.sql"

fill-testdata:
	docker exec -i $(CONTAINER_NAME) mariadb -h 127.0.0.1 -P 3306 -u $(DB_USER) -p$(DB_PASSWORD) $(DB_NAME) < testdata/fill_test_data.sql
	@echo "Test data loaded (3 users, 3 companies, products, addresses, contacts, etc.). Password: password"

new-migration:
	@TIMESTAMP=$$(date +%Y%m%d%H%M%S); \
	MIGRATION_NAME="$(NAME)"; \
	if [ -z "$$MIGRATION_NAME" ]; then MIGRATION_NAME="migration"; fi; \
	FILENAME="migrations/$${TIMESTAMP}_$${MIGRATION_NAME}.sql"; \
	printf '%s\n' \
		'-- +migrate Up' \
		'' \
		'-- TODO: Add your migration here' \
		'' \
		'-- +migrate Down' \
		'' \
		'-- TODO: Add rollback here' \
		> "$$FILENAME"; \
	echo "Created migration: $$FILENAME"

db-shell:
	docker exec -it $(CONTAINER_NAME) mariadb -u $(DB_USER) -p$(DB_PASSWORD) $(DB_NAME)
