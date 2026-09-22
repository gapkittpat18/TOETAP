USE tapsole_v0;

CREATE TABLE IF NOT EXISTS webhook_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(30) NOT NULL DEFAULT 'strava',
    subscription_id BIGINT NULL,
    object_type VARCHAR(30) NOT NULL,
    object_id BIGINT NOT NULL,
    aspect_type VARCHAR(30) NOT NULL,
    owner_id BIGINT NULL,
    event_time BIGINT NULL,
    payload LONGTEXT NULL,
    status ENUM('RECEIVED','PROCESSING','APPLIED','IGNORED','ERROR') NOT NULL DEFAULT 'RECEIVED',
    message VARCHAR(500) NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    UNIQUE KEY uq_webhook_event (provider, subscription_id, object_type, object_id, aspect_type, event_time),
    KEY ix_webhook_status (status)
);

CREATE TABLE IF NOT EXISTS webhook_settings (
    id TINYINT UNSIGNED PRIMARY KEY,
    verify_token VARCHAR(100) NOT NULL,
    subscription_id BIGINT NULL,
    callback_url VARCHAR(500) NULL,
    auto_apply TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO webhook_settings(id,verify_token,auto_apply)
VALUES(1,'CHANGE_ME_TAPSOLE_VERIFY_TOKEN',1)
ON DUPLICATE KEY UPDATE id=id;
