-- Add English columns for companies/products

-- +migrate Up
ALTER TABLE companies
    ADD COLUMN IF NOT EXISTS name_en VARCHAR(255) NULL DEFAULT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS main_activity_en VARCHAR(255) NULL DEFAULT NULL AFTER main_activity;

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS name_en VARCHAR(255) NULL DEFAULT NULL AFTER name;

-- +migrate Down
ALTER TABLE companies
    DROP COLUMN IF EXISTS name_en,
    DROP COLUMN IF EXISTS main_activity_en;

ALTER TABLE products
    DROP COLUMN IF EXISTS name_en;
