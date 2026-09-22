TapSole V0.6.1 — LIVE RECENT RUNS
====================================

WHAT CHANGED
- Home > Recent Runs now reads LIVE from Strava API.
- Shows the latest 3 running activities.
- Run / TrailRun / VirtualRun only.
- Ride and other activity types are excluded.
- Maps Strava gear_id back to the TapSole shoe name when linked.
- The old local `activities` table is no longer used to build the Home Recent Runs list.
- Existing V0.5 Latest Tap Wins / 6-hour matching logic is untouched.
- NFC registration from V0.6 is untouched.

INSTALL
Copy ONLY:
    index.php

to:
    C:\xampp\htdocs\tapsole\index.php

and overwrite the existing V0.6 index.php.

No SQL migration.
Do not change:
- config/database.php
- config/strava.php
- tap.php
- webhook/processor.php

EXPECTED RESULT
If Strava Latest Activities shows:
    Afternoon Run — 0.20 km

Home Recent Runs should also show that run immediately (assuming the Strava connection is valid).
If its gear ID is linked to a TapSole shoe, Home shows the TapSole shoe name instead of the raw gear ID.
