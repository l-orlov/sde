
DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `nuestra_historia` varchar(700) DEFAULT NULL,
  `organization_type` varchar(100) DEFAULT NULL,
  `main_activity` varchar(100) DEFAULT NULL,
  `main_activity_en` varchar(255) DEFAULT NULL,
  `moderation_status` enum('pending','approved') NOT NULL DEFAULT 'pending',
  `moderation_date` int(10) unsigned DEFAULT NULL,
  `moderated_by` int(10) unsigned DEFAULT NULL,
  `created_at` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  `updated_at` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_user_uidx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
DROP TABLE IF EXISTS `company_addresses`;
CREATE TABLE `company_addresses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `type` enum('legal','admin') NOT NULL,
  `street` varchar(255) DEFAULT NULL,
  `street_number` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `floor` varchar(50) DEFAULT NULL,
  `apartment` varchar(50) DEFAULT NULL,
  `locality` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `created_at` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_addresses_company_idx` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
DROP TABLE IF EXISTS `company_contacts`;
CREATE TABLE `company_contacts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `area_code` varchar(20) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_contacts_company_idx` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
DROP TABLE IF EXISTS `company_data`;
CREATE TABLE `company_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `current_markets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`current_markets`)),
  `target_markets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_markets`)),
  `differentiation_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`differentiation_factors`)),
  `needs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`needs`)),
  `competitiveness` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`competitiveness`)),
  `logistics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`logistics`)),
  `expectations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`expectations`)),
  `consents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`consents`)),
  `created_at` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  `updated_at` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_data_company_uidx` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
DROP TABLE IF EXISTS `company_social_networks`;
CREATE TABLE `company_social_networks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `network_type` varchar(100) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `created_at` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_social_company_idx` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL DEFAULT 'product_photo',
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(10) unsigned DEFAULT NULL,
  `storage_type` varchar(20) NOT NULL DEFAULT 'local',
  `is_temporary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  PRIMARY KEY (`id`),
  KEY `files_product_idx` (`product_id`),
  KEY `files_user_idx` (`user_id`),
  KEY `files_type_idx` (`file_type`),
  KEY `files_path_idx` (`file_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  PRIMARY KEY (`id`),
  KEY `password_reset_tokens_token_idx` (`token`),
  KEY `password_reset_tokens_user_expires_idx` (`user_id`,`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `type` enum('product','service') NOT NULL DEFAULT 'product',
  `activity` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tariff_code` varchar(20) DEFAULT NULL COMMENT 'NCM/HS e.g. 0602.90.90.100X',
  `annual_export` varchar(100) DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `created_at` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  `updated_at` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  `deleted_at` int(10) unsigned DEFAULT NULL COMMENT 'Unix timestamp when soft-deleted; NULL = not deleted',
  PRIMARY KEY (`id`),
  KEY `products_company_idx` (`company_id`),
  KEY `products_user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
DROP TABLE IF EXISTS `schema_migrations`;
CREATE TABLE `schema_migrations` (
  `name` varchar(255) NOT NULL,
  `applied_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `tax_id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(32) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  `updated_at` int(10) unsigned NOT NULL DEFAULT unix_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_phone_uidx` (`phone`),
  UNIQUE KEY `users_email_uidx` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


