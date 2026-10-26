-- Fresh installation for the Pet Grooming Salon application.
-- Existing installations must use Database/migrations/001_user_auth_audit.sql instead.
CREATE DATABASE IF NOT EXISTS pet_grooming_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS pet_owners (
    owner_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    added_by INT NULL,
    added_at DATETIME NULL DEFAULT NULL,
    last_updated_by INT NULL,
    last_updated DATETIME NULL DEFAULT NULL,
    KEY idx_pet_owners_added_by (added_by),
    KEY idx_pet_owners_last_updated_by (last_updated_by),
    CONSTRAINT fk_pet_owners_added_by FOREIGN KEY (added_by)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pet_owners_last_updated_by FOREIGN KEY (last_updated_by)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pets (
    pet_id INT AUTO_INCREMENT PRIMARY KEY,
    pet_name VARCHAR(100) NOT NULL,
    species VARCHAR(50) NOT NULL,
    breed VARCHAR(50) NULL,
    grooming_date DATE NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    owner_id INT NOT NULL,
    added_by INT NULL,
    added_at DATETIME NULL DEFAULT NULL,
    last_updated_by INT NULL,
    last_updated DATETIME NULL DEFAULT NULL,
    KEY idx_pets_owner_id (owner_id),
    KEY idx_pets_added_by (added_by),
    KEY idx_pets_last_updated_by (last_updated_by),
    CONSTRAINT fk_pets_pet_owner FOREIGN KEY (owner_id)
        REFERENCES pet_owners(owner_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pets_added_by FOREIGN KEY (added_by)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pets_last_updated_by FOREIGN KEY (last_updated_by)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
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
