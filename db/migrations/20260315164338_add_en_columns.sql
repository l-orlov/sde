-- Add English columns for companies, company_addresses, company_contacts, and products

-- +migrate Up
ALTER TABLE companies
    ADD COLUMN IF NOT EXISTS name_en VARCHAR(255) NULL DEFAULT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS main_activity_en VARCHAR(255) NULL DEFAULT NULL AFTER main_activity,
    ADD COLUMN IF NOT EXISTS organization_type_en VARCHAR(100) NULL DEFAULT NULL AFTER organization_type;

ALTER TABLE company_addresses
    ADD COLUMN IF NOT EXISTS locality_en VARCHAR(255) NULL DEFAULT NULL AFTER locality,
    ADD COLUMN IF NOT EXISTS department_en VARCHAR(255) NULL DEFAULT NULL AFTER department;

ALTER TABLE company_contacts
    ADD COLUMN IF NOT EXISTS position_en VARCHAR(255) NULL DEFAULT NULL AFTER position;

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS name_en VARCHAR(255) NULL DEFAULT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS description_en TEXT NULL DEFAULT NULL AFTER description,
    ADD COLUMN IF NOT EXISTS annual_export_en VARCHAR(100) NULL DEFAULT NULL AFTER annual_export,
    ADD COLUMN IF NOT EXISTS certifications_en TEXT NULL DEFAULT NULL AFTER certifications,
    ADD COLUMN IF NOT EXISTS current_markets_en JSON NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS target_markets_en JSON NULL DEFAULT NULL;

-- +migrate Down
ALTER TABLE companies
    DROP COLUMN IF EXISTS name_en,
    DROP COLUMN IF EXISTS main_activity_en,
    DROP COLUMN IF EXISTS organization_type_en;

ALTER TABLE company_addresses
    DROP COLUMN IF EXISTS locality_en,
    DROP COLUMN IF EXISTS department_en;

ALTER TABLE company_contacts
    DROP COLUMN IF EXISTS position_en;

ALTER TABLE products
    DROP COLUMN IF EXISTS name_en,
    DROP COLUMN IF EXISTS description_en,
    DROP COLUMN IF EXISTS annual_export_en,
    DROP COLUMN IF EXISTS certifications_en,
    DROP COLUMN IF EXISTS current_markets_en,
    DROP COLUMN IF EXISTS target_markets_en;
