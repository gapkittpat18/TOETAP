-- TOETAP V1.6 — Strava multi-user ownership hardening
USE tapsole_v0;

-- A Strava athlete must never map to two TOETAP users.
-- If this fails, inspect duplicate athlete_id rows before continuing.
CREATE UNIQUE INDEX IF NOT EXISTS UX_strava_connections_athlete
ON strava_connections(athlete_id);

CREATE INDEX IF NOT EXISTS IX_webhook_events_owner
ON webhook_events(owner_id,status);

-- V1.1 may already have these columns/index. Kept compatible.
ALTER TABLE activities
  ADD COLUMN IF NOT EXISTS source VARCHAR(20) NOT NULL DEFAULT 'STRAVA' AFTER user_shoe_id,
  ADD COLUMN IF NOT EXISTS external_activity_id VARCHAR(100) NULL AFTER source;

UPDATE activities
SET source='STRAVA',
    external_activity_id=CAST(strava_activity_id AS CHAR)
WHERE strava_activity_id IS NOT NULL
  AND (external_activity_id IS NULL OR external_activity_id='');

CREATE UNIQUE INDEX IF NOT EXISTS UX_activities_source_external
ON activities(source,external_activity_id);
