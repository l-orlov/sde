-- Separate admin login (is_admin) from public catalog / oferta PDF / landing / search.
-- 1 = user company data participates in business exports (default for existing rows).
-- 0 = hidden from public listings and institutional oferta PDFs (e.g. internal staff accounts).

-- +migrate Up
ALTER TABLE `users`
  ADD COLUMN `include_in_business_exports` tinyint(1) NOT NULL DEFAULT 1
  AFTER `is_admin`;

-- +migrate Down
ALTER TABLE `users`
  DROP COLUMN `include_in_business_exports`;
