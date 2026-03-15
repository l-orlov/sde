-- Add English columns for companies and products (name, main_activity, description, annual_export, certifications)

-- +migrate Up
ALTER TABLE companies
    ADD COLUMN IF NOT EXISTS name_en VARCHAR(255) NULL DEFAULT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS main_activity_en VARCHAR(255) NULL DEFAULT NULL AFTER main_activity;

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS name_en VARCHAR(255) NULL DEFAULT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS description_en TEXT NULL DEFAULT NULL AFTER description,
    ADD COLUMN IF NOT EXISTS annual_export_en VARCHAR(100) NULL DEFAULT NULL AFTER annual_export,
    ADD COLUMN IF NOT EXISTS certifications_en TEXT NULL DEFAULT NULL AFTER certifications;

-- +migrate Down
ALTER TABLE companies
    DROP COLUMN IF EXISTS name_en,
    DROP COLUMN IF EXISTS main_activity_en;

ALTER TABLE products
    DROP COLUMN IF EXISTS name_en,
    DROP COLUMN IF EXISTS description_en,
    DROP COLUMN IF EXISTS annual_export_en,
    DROP COLUMN IF EXISTS certifications_en;
