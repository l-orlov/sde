CREATE TABLE users (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100)    NOT NULL,
    last_name  VARCHAR(100)    NOT NULL,
    email      VARCHAR(255)    NOT NULL,
    phone      VARCHAR(32)     NOT NULL,
    password   VARCHAR(50)     NOT NULL,
    created_at INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),
    updated_at INT UNSIGNED    NOT NULL DEFAULT UNIX_TIMESTAMP(),

    PRIMARY KEY (`id`),
    UNIQUE KEY `users_phone_idx` (`phone`),
    UNIQUE KEY `users_email_idx` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
