USE tapsole_v0;

-- TapSole V0.5 — Latest Tap Wins
-- No schema change is required.
-- Clean up any old unused selections left from V0.4 testing so the next NFC tap starts clean.
UPDATE shoe_selections
SET used = 1
WHERE used = 0;
