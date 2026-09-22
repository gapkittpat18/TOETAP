USE tapsole_v0;

ALTER TABLE user_shoes
    ADD COLUMN strava_gear_id VARCHAR(64) NULL AFTER target_km,
    ADD COLUMN strava_gear_name VARCHAR(255) NULL AFTER strava_gear_id;

ALTER TABLE activities
    ADD COLUMN match_status ENUM('UNMATCHED','PREVIEW','APPLIED','SKIPPED') NOT NULL DEFAULT 'UNMATCHED',
    ADD COLUMN shoe_selection_id BIGINT UNSIGNED NULL,
    ADD COLUMN original_gear_id VARCHAR(64) NULL,
    ADD COLUMN proposed_gear_id VARCHAR(64) NULL,
    ADD INDEX ix_activity_match_status (match_status);
