CREATE DATABASE IF NOT EXISTS tapsole_v0
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE tapsole_v0;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS activity_metrics;
DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS shoe_selections;
DROP TABLE IF EXISTS tag_events;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS user_shoes;
DROP TABLE IF EXISTS shoe_models;
DROP TABLE IF EXISTS shoe_brands;
DROP TABLE IF EXISTS strava_connections;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(100) NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE shoe_brands (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE shoe_models (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    brand_id INT UNSIGNED NOT NULL,
    model_name VARCHAR(150) NOT NULL,
    category VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_model_brand FOREIGN KEY (brand_id) REFERENCES shoe_brands(id)
) ENGINE=InnoDB;

CREATE TABLE user_shoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    shoe_model_id INT UNSIGNED NULL,
    custom_brand VARCHAR(100) NULL,
    custom_model VARCHAR(150) NULL,
    nickname VARCHAR(100) NULL,
    size VARCHAR(30) NULL,
    color VARCHAR(100) NULL,
    purchase_price DECIMAL(10,2) NULL,
    purchase_currency VARCHAR(3) NOT NULL DEFAULT 'THB',
    initial_km DECIMAL(10,2) NOT NULL DEFAULT 0,
    target_km DECIMAL(10,2) NOT NULL DEFAULT 500,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_shoe_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_shoe_model FOREIGN KEY (shoe_model_id) REFERENCES shoe_models(id)
) ENGINE=InnoDB;

CREATE TABLE tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tag_code VARCHAR(64) NOT NULL UNIQUE,
    user_shoe_id INT UNSIGNED NULL,
    issued_by_tapsole TINYINT(1) NOT NULL DEFAULT 1,
    activation_status ENUM('NEW','ACTIVE','DISABLED') NOT NULL DEFAULT 'NEW',
    activated_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tag_shoe FOREIGN KEY (user_shoe_id) REFERENCES user_shoes(id)
) ENGINE=InnoDB;

CREATE TABLE tag_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tag_id INT UNSIGNED NOT NULL,
    event_type VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_tag FOREIGN KEY (tag_id) REFERENCES tags(id)
) ENGINE=InnoDB;

CREATE TABLE shoe_selections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    user_shoe_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    selected_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_sel_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_sel_shoe FOREIGN KEY (user_shoe_id) REFERENCES user_shoes(id),
    CONSTRAINT fk_sel_tag FOREIGN KEY (tag_id) REFERENCES tags(id),
    INDEX ix_selection_user_time (user_id, selected_at),
    INDEX ix_selection_used (used)
) ENGINE=InnoDB;

CREATE TABLE activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    user_shoe_id INT UNSIGNED NULL,
    strava_activity_id BIGINT UNSIGNED NULL UNIQUE,
    activity_type VARCHAR(50) NULL,
    distance_m DECIMAL(12,2) NULL,
    moving_time_s INT UNSIGNED NULL,
    start_date DATETIME NULL,
    assigned_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_activity_shoe FOREIGN KEY (user_shoe_id) REFERENCES user_shoes(id)
) ENGINE=InnoDB;

CREATE TABLE activity_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id BIGINT UNSIGNED NOT NULL,
    avg_hr DECIMAL(6,2) NULL,
    avg_cadence DECIMAL(6,2) NULL,
    avg_power DECIMAL(8,2) NULL,
    elevation_gain_m DECIMAL(10,2) NULL,
    CONSTRAINT fk_metric_activity FOREIGN KEY (activity_id) REFERENCES activities(id)
) ENGINE=InnoDB;

CREATE TABLE strava_connections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    athlete_id BIGINT UNSIGNED NULL,
    access_token TEXT NULL,
    refresh_token TEXT NULL,
    token_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_strava_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

INSERT INTO users (email,name) VALUES ('test@tapsole.local','Gap');
INSERT INTO tags (tag_code,issued_by_tapsole,activation_status)
VALUES ('TS000001',1,'NEW');
