TAPSOLE V0.3.1 DEV TEST

Copy/overwrite into your existing C:\xampp\htdocs\tapsole folder.
KEEP your real config/strava.php.

No new SQL migration is required beyond V0.3's v03_upgrade.sql.

Open:
http://localhost/tapsole/strava/dev_test.php

Recommended:
1. Choose your short 0.28 km existing Run.
2. Choose the TapSole shoe linked to a Strava gear.
3. SIMULATE TAP -10 MIN.
4. Open Safe Run Match and confirm the matching engine sees it.
5. Return to DEV Test.
6. APPLY TEST GEAR. This changes the existing Strava activity.
7. Verify on Strava.
8. RESTORE ORIGINAL GEAR.

This page exists only for local development. Delete/disable it before any production deployment.
