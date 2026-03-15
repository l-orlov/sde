# База данных и миграции

## Формат миграций

Имя файла: **YYYYMMDDHHMMSS_description.sql** (временная метка для порядка). Создать новый файл: `make new-migration NAME=add_table`.

Внутри файла — секции Up и Down:

```sql
-- +migrate Up
ALTER TABLE ...

-- +migrate Down
ALTER TABLE ...
```

Локальные скрипты при `migrate-up` выполняют только блок между `-- +migrate Up` и `-- +migrate Down`, при `migrate-down` — только блок после `-- +migrate Down`.

## Локально (Docker + Make)

Все команды можно запускать из папки **db/** (`cd db && make up-db`).

- `make up-db` — поднять MariaDB
- `make down-db` — остановить MariaDB
- `make clean` — остановить и удалить контейнер и volume (полная очистка, данных не остаётся)
- `make rebuild` — полный сброс: clean + up-db + db-wait + migrate-up + fill-testdata (одна команда)
- `make db-wait` — дождаться готовности
- `make migrate-up` — применить миграции (только секции Up)
- `make migrate-down` — откатить последнюю миграцию (секция Down)
- `make schema-dump` — выгрузить текущую схему в **db/schema.sql** (без данных). Вызывается автоматически после `migrate-up` и `migrate-down`.
- `make new-migration [NAME=описание]` — создать новый файл миграции с текущей временной меткой.
- `make db-shell` — открыть консоль MariaDB (под пользователем из docker-compose: user/db). Для входа под root: `docker exec -it sde_mariadb mariadb -u root -p`, пароль: `rootpassword`, затем `USE db;`

Типичная последовательность:

```bash
make up-db && make db-wait && make migrate-up
```

После `migrate-up` и `migrate-down` схема автоматически выгружается в **schema.sql**.

### Тестовые данные

В **db/testdata/fill_test_data.sql** — тестовые пользователи, компании и связанные записи (по 3 штуки). Загрузить после миграций:

```bash
make fill-testdata
```

Пароль у всех тестовых пользователей: **password**. Один из пользователей (id=3) — администратор.

Полный сброс и поднятие с тестовыми данными — одной командой:

```bash
make rebuild
```

## Общая схема (schema.sql)

Файл **db/schema.sql** — полная схема БД после применения всех миграций. Генерируется локально:

```bash
make schema-dump
```

Имеет смысл коммитить `db/schema.sql` в репо, чтобы все видели текущее состояние схемы без запуска миграций.

## Сервер: только phpMyAdmin (ручное применение)

Без доступа по SSH и без скриптов на сервере миграции накатываются вручную.

1. Откройте БД в phpMyAdmin → вкладка «SQL».
2. Для каждой миграции **по порядку** (по имени файла: сначала меньшая временная метка):
   - откройте файл из **db/migrations/** (например, `20260315164338_add_en_columns.sql`);
   - скопируйте в phpMyAdmin **только SQL между строками** `-- +migrate Up` и `-- +migrate Down` (сами эти строки не выполняйте);
   - выполните скопированный SQL;
   - затем выполните (INSERT IGNORE — не упадёт, если запись уже есть):  
     `INSERT IGNORE INTO schema_migrations (name) VALUES ('20260315164338_add_en_columns.sql');`
3. Таблицу **schema_migrations** создаёт первая по имени миграция (минимальная временная метка) — сначала выполните её блок Up и вставьте запись с именем этого файла.

**Удобный вариант:** после того как выполнили блоки Up всех миграций по порядку, можно один раз выполнить в phpMyAdmin файл **db/for_prod/record_schema_migrations.sql** — в нём собраны все `INSERT IGNORE INTO schema_migrations (name) VALUES (...);` для текущих миграций. Уже существующие записи не дублируются, добавляются только отсутствующие.

Если таблицы/колонки уже есть (старая БД), в миграциях используется `CREATE TABLE IF NOT EXISTS` и `ADD COLUMN IF NOT EXISTS`, повторный прогон не должен ломать БД.

**Если вы уже накатывали миграции со старыми именами:** после перехода на новые имена обновите записи в `schema_migrations` на актуальные имена файлов (например, `20260315164336_create_migrations_table.sql`), либо локально очистите `schema_migrations` и снова выполните `make migrate-up` — повторное применение безопасно.

### Откат вручную (migrate-down)

1. Узнать последнюю миграцию:  
   `SELECT name FROM schema_migrations ORDER BY name DESC LIMIT 1;`
2. В соответствующем файле из **db/migrations/** скопировать в phpMyAdmin **только SQL после строки** `-- +migrate Down` и выполнить его.
3. Удалить запись:  
   `DELETE FROM schema_migrations WHERE name = '20260315164338_add_en_columns.sql';`
