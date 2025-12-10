CREATE TABLE users (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    company_name VARCHAR(255)  NOT NULL,
    tax_id     VARCHAR(50)     NOT NULL,
    email      VARCHAR(255)    NOT NULL,
    phone      VARCHAR(32)     NOT NULL,
    password   VARCHAR(50)     NOT NULL,
    created_at INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),
    updated_at INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    UNIQUE KEY `users_phone_uidx` (`phone`),
    UNIQUE KEY `users_email_uidx` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица компаний
CREATE TABLE companies (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id             INT UNSIGNED    NOT NULL,
    name                VARCHAR(255)    NOT NULL,
    tax_id              VARCHAR(50),
    legal_name          VARCHAR(255),
    start_date          INT UNSIGNED,
    website             VARCHAR(255),
    organization_type   VARCHAR(100),
    main_activity       VARCHAR(100),
    created_at          INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),
    updated_at          INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    UNIQUE KEY `companies_user_uidx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Адреса компаний
CREATE TABLE company_addresses (
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

-- Контактные лица компаний
CREATE TABLE company_contacts (
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

-- Социальные сети компаний
CREATE TABLE company_social_networks (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    company_id      INT UNSIGNED    NOT NULL,
    network_type    VARCHAR(100),
    url             VARCHAR(500),
    created_at      INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    KEY `company_social_company_idx` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Дополнительные данные компаний (JSON)
CREATE TABLE company_data (
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

-- История экспорта компаний
CREATE TABLE company_export_history (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    company_id  INT UNSIGNED    NOT NULL,
    year        INT UNSIGNED    NOT NULL,
    amount_usd  DECIMAL(15,2),
    created_at  INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    KEY `company_export_company_idx` (`company_id`),
    KEY `company_export_year_idx` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица товаров (расширенная)
CREATE TABLE products (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    company_id          INT UNSIGNED,
    user_id             INT UNSIGNED    NOT NULL,
    is_main             BOOLEAN         NOT NULL DEFAULT FALSE,
    name                VARCHAR(255)    NOT NULL,
    tariff_code         VARCHAR(50),
    description         TEXT,
    volume_unit         VARCHAR(50),
    volume_amount       VARCHAR(50),
    annual_export       VARCHAR(100),
    certifications      TEXT,
    created_at          INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),
    updated_at          INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    KEY `products_company_idx` (`company_id`),
    KEY `products_user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица файлов (универсальная)
CREATE TABLE files (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    product_id  INT UNSIGNED,
    user_id     INT UNSIGNED    NOT NULL,
    
    -- Универсальный путь (работает для обоих хранилищ)
    file_path   VARCHAR(500)    NOT NULL,
    
    -- Метаданные
    file_name   VARCHAR(255)    NOT NULL,
    file_type   VARCHAR(50)     NOT NULL DEFAULT 'product_photo',
    mime_type   VARCHAR(100),
    file_size   INT UNSIGNED,
    
    -- Тип хранилища (для совместимости)
    storage_type VARCHAR(20)    NOT NULL DEFAULT 'local',
    
    -- Временный файл (загружен, но еще не сохранен в форме)
    is_temporary TINYINT(1)     NOT NULL DEFAULT 0,
    
    created_at  INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    KEY `files_product_idx` (`product_id`),
    KEY `files_user_idx` (`user_id`),
    KEY `files_type_idx` (`file_type`),
    KEY `files_path_idx` (`file_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
