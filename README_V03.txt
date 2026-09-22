TAPSOLE V0.3

IMPORTANT: Keep your existing config/strava.php containing your own credentials.
The ZIP includes a placeholder config file from V0.2. Do not overwrite your real credentials with the placeholder.

1. Back up C:\xampp\htdocs\tapsole
2. Copy V0.3 files into the existing tapsole folder.
3. In phpMyAdmin select tapsole_v0 and import v03_upgrade.sql ONCE.
4. Open:
   http://localhost/tapsole/strava/gear_link.php
   Link your TapSole shoe to the correct Strava gear.
5. Tap your shoe from TapSole Home to create a NEW selection.
6. For testing against an existing run, the tap must be before that run and within 2 hours.
   For a real test, tap before your next run, complete/sync it, then open:
   http://localhost/tapsole/strava/match.php
7. V0.3 previews the proposed match. Nothing changes on Strava until APPLY TO STRAVA is pressed.
8. After a successful apply, the selection is marked used.

Matching safety:
- Run / TrailRun / VirtualRun only
- unused selection only
- tap must occur before run
- maximum 2 hours before run
- if more than one eligible selection exists, no automatic candidate is shown

Next version after real testing: webhook + automatic apply.
