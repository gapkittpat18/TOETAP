USE tapsole_v0;

-- Remove unused simulated selections created by older DEV versions.
-- Recreate a fresh one from V0.3.3 DEV Test.
DELETE FROM shoe_selections WHERE used = 0;
