USE tapsole_v0;

-- V0.3.2 switches TapSole internal timestamps to UTC.
-- Existing development selections can contain mixed local/UTC values,
-- so clear ONLY unused selections and recreate them through DEV Test.
DELETE FROM shoe_selections WHERE used = 0;

-- Existing applied test activity timestamps are not required for matching.
-- No production data migration is needed at this prototype stage.
