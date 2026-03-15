-- Initial application schema (single source of truth for new installs).
-- Do not modify; add new migrations for changes.

-- +migrate Up
CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    company_name VARCHAR(255)  NOT NULL,
    tax_id     VARCHAR(50)     NOT NULL,
    email      VARCHAR(255)    NOT NULL,
    phone      VARCHAR(32)     NOT NULL,
    password   VARCHAR(255)    NOT NULL,
    is_admin   TINYINT(1)      NOT NULL DEFAULT 0,
    created_at INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),
    updated_at INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    UNIQUE KEY `users_phone_uidx` (`phone`),
    UNIQUE KEY `users_email_uidx` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    token       VARCHAR(64)     NOT NULL,
    expires_at  INT UNSIGNED    NOT NULL,
    created_at  INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    KEY `password_reset_tokens_token_idx` (`token`),
    KEY `password_reset_tokens_user_expires_idx` (`user_id`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS companies (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id             INT UNSIGNED    NOT NULL,
    name                VARCHAR(255)    NOT NULL,
    name_en             VARCHAR(255)    NULL     DEFAULT NULL,
    tax_id              VARCHAR(50),
    legal_name          VARCHAR(255),
    start_date          DATE,
    website             VARCHAR(255),
    nuestra_historia    VARCHAR(700),
    organization_type   VARCHAR(100),
    main_activity       VARCHAR(100),
    main_activity_en    VARCHAR(255)    NULL     DEFAULT NULL,
    moderation_status   ENUM('pending', 'approved') NOT NULL DEFAULT 'pending',
    moderation_date     INT UNSIGNED    NULL,
    moderated_by        INT UNSIGNED    NULL,
    created_at          INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),
    updated_at          INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    UNIQUE KEY `companies_user_uidx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS company_addresses (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    company_id      INT UNSIGNED    NOT NULL,
    type            ENUM('legal', 'admin') NOT NULL,
    street          VARCHAR(255),
    street_number   VARCHAR(50),
    postal_code     VARCHAR(20),
    floor           VARCHAR(50),
    apartment       VARCHAR(50),
    locality        VARCHAR(255),
    department      VARCHAR(255),
    created_at      INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    KEY `company_addresses_company_idx` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS company_contacts (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    company_id      INT UNSIGNED    NOT NULL,
    contact_person  VARCHAR(255),
    position        VARCHAR(255),
    email           VARCHAR(255),
    area_code       VARCHAR(20),
    phone           VARCHAR(50),
    created_at      INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    KEY `company_contacts_company_idx` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS company_social_networks (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    company_id      INT UNSIGNED    NOT NULL,
    network_type    VARCHAR(100),
    url             VARCHAR(500),
    created_at      INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    KEY `company_social_company_idx` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS company_data (
    id                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    company_id             INT UNSIGNED    NOT NULL,
    current_markets        JSON,
    target_markets         JSON,
    differentiation_factors JSON,
    needs                  JSON,
    competitiveness        JSON,
    logistics              JSON,
    expectations           JSON,
    consents               JSON,
    created_at             INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),
    updated_at             INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    UNIQUE KEY `company_data_company_uidx` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    company_id          INT UNSIGNED,
    user_id             INT UNSIGNED    NOT NULL,
    is_main             BOOLEAN         NOT NULL DEFAULT FALSE,
    type                ENUM('product', 'service') NOT NULL DEFAULT 'product',
    activity            VARCHAR(255)    NULL,
    name                VARCHAR(255)    NOT NULL,
    name_en             VARCHAR(255)    NULL     DEFAULT NULL,
    description         TEXT,
    tariff_code         VARCHAR(20)     NULL COMMENT 'NCM/HS e.g. 0602.90.90.100X',
    annual_export       VARCHAR(100),
    certifications      TEXT,
    created_at          INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),
    updated_at          INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),
    deleted_at          INT UNSIGNED    NULL     DEFAULT NULL COMMENT 'Unix timestamp when soft-deleted; NULL = not deleted',

    PRIMARY KEY (`id`),
    KEY `products_company_idx` (`company_id`),
    KEY `products_user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS files (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    product_id  INT UNSIGNED,
    user_id     INT UNSIGNED    NOT NULL,
    file_path   VARCHAR(500)    NOT NULL,
    file_name   VARCHAR(255)    NOT NULL,
    file_type   VARCHAR(50)     NOT NULL DEFAULT 'product_photo',
    mime_type   VARCHAR(100),
    file_size   INT UNSIGNED,
    storage_type VARCHAR(20)    NOT NULL DEFAULT 'local',
    is_temporary TINYINT(1)     NOT NULL DEFAULT 0,
    created_at  INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    KEY `files_product_idx` (`product_id`),
    KEY `files_user_idx` (`user_id`),
    KEY `files_type_idx` (`file_type`),
    KEY `files_path_idx` (`file_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- +migrate Down
DROP TABLE IF EXISTS files;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS company_data;
DROP TABLE IF EXISTS company_social_networks;
DROP TABLE IF EXISTS company_contacts;
DROP TABLE IF EXISTS company_addresses;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS users;
