-- One-time upgrade for an existing pet_grooming_db created from the original schema.
-- This migration preserves all owner and pet rows. Legacy audit attribution remains NULL.
USE pet_grooming_db;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    address VARCHAR(255) NOT NULL,
    age TINYINT UNSIGNED NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    audit_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    performed_by INT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    entity_type ENUM('Pet Owner', 'Pet') NOT NULL,
    record_id INT NOT NULL,
    old_values LONGTEXT NULL,
    new_values LONGTEXT NULL,
    performed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_logs_performed_by (performed_by),
    KEY idx_audit_logs_event_time (performed_at, audit_id),
    KEY idx_audit_logs_entity_record (entity_type, record_id),
    CONSTRAINT fk_audit_logs_performed_by FOREIGN KEY (performed_by)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Run once. Existing rows receive NULL in all four audit columns; no old actor is fabricated.
ALTER TABLE pet_owners
    ADD COLUMN added_by INT NULL,
    ADD COLUMN added_at DATETIME NULL DEFAULT NULL,
    ADD COLUMN last_updated_by INT NULL,
    ADD COLUMN last_updated DATETIME NULL DEFAULT NULL,
    ADD KEY idx_pet_owners_added_by (added_by),
    ADD KEY idx_pet_owners_last_updated_by (last_updated_by),
    ADD CONSTRAINT fk_pet_owners_added_by FOREIGN KEY (added_by)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_pet_owners_last_updated_by FOREIGN KEY (last_updated_by)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE pets
    ADD COLUMN added_by INT NULL,
    ADD COLUMN added_at DATETIME NULL DEFAULT NULL,
    ADD COLUMN last_updated_by INT NULL,
    ADD COLUMN last_updated DATETIME NULL DEFAULT NULL,
    ADD KEY idx_pets_added_by (added_by),
    ADD KEY idx_pets_last_updated_by (last_updated_by),
    ADD CONSTRAINT fk_pets_added_by FOREIGN KEY (added_by)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_pets_last_updated_by FOREIGN KEY (last_updated_by)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE;
