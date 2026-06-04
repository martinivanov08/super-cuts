CREATE DATABASE IF NOT EXISTS barbershop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE barbershop;

CREATE TABLE IF NOT EXISTS reservations (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)    NOT NULL,
    phone      VARCHAR(30)     NOT NULL,
    email      VARCHAR(100),
    service    VARCHAR(100)    NOT NULL,
    barber     VARCHAR(100)    NOT NULL,
    date       DATE            NOT NULL,
    time       TIME            NOT NULL,
    notes      TEXT,
    status     VARCHAR(20)     NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
);
