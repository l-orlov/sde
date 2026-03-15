-- Запись применённых миграций для prod (phpMyAdmin).
-- После того как вручную выполнили блоки Up из db/migrations/*.sql по порядку,
-- выполните этот файл в phpMyAdmin — в schema_migrations добавятся имена миграций.
-- INSERT IGNORE: уже существующие записи пропускаются, новые добавляются.

INSERT IGNORE INTO schema_migrations (name) VALUES ('20260315164336_create_migrations_table.sql');
INSERT IGNORE INTO schema_migrations (name) VALUES ('20260315164337_initial_schema.sql');
INSERT IGNORE INTO schema_migrations (name) VALUES ('20260315164338_add_en_columns.sql');
