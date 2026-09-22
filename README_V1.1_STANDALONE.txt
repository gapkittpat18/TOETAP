TapSole V1.1 — Standalone Core
================================
Goal: TapSole works without a Strava account.

INSTALL
1. Overlay this package on the current TapSole folder.
2. Run v110_standalone.sql once.
3. Keep the existing config/, tap.php, webhook/ and strava/ core files.

WHAT CHANGED
- Strava is optional.
- Added add_run.php for manual runs.
- Latest tapped shoe is preselected for manual run entry.
- activities now has generic source + external_activity_id fields.
- Existing Strava rows are migrated to source=STRAVA.
- Manual rows use source=MANUAL and do not need strava_activity_id.
- Insights now reads TapSole's local activities table, not live Strava.
- Therefore Insights works for manual runs and Strava-imported/matched runs together.
- Existing Strava automatic matching/update flow remains compatible.
- No GPS recording is added.

NEXT BACKEND STEP
For beta/multi-source sync, make each importer (Strava/Garmin/etc.) write to the same activities model and deduplicate by source + external_activity_id.
