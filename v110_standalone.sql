-- TOETAP V1.1 — standalone activity source support
-- Safe additive migration for the current prototype.
ALTER TABLE activities
  ADD COLUMN IF NOT EXISTS source VARCHAR(20) NOT NULL DEFAULT 'STRAVA' AFTER user_shoe_id,
  ADD COLUMN IF NOT EXISTS external_activity_id VARCHAR(100) NULL AFTER source;

UPDATE activities
SET source='STRAVA',
    external_activity_id=CAST(strava_activity_id AS CHAR)
WHERE strava_activity_id IS NOT NULL
  AND (external_activity_id IS NULL OR external_activity_id='');

CREATE INDEX IF NOT EXISTS IX_activities_user_start
ON activities(user_id,start_date);

CREATE INDEX IF NOT EXISTS IX_activities_source_external
ON activities(source,external_activity_id);
