TAPSOLE V0.3.3 - EXACT UTC + ONE TAP / ONE RUN

FIXES
1. Strava UTC ISO timestamps are parsed with DateTimeImmutable and explicitly converted to UTC.
   SIMULATE TAP -10 MIN now creates exactly activity start UTC minus 10 minutes.
2. Matching is one-to-one:
   - one selection can match only one activity
   - it chooses the FIRST eligible running activity after the tap
   - maximum gap is 2 hours
   - Ride is ignored
3. Runs are evaluated oldest -> newest to avoid one tap appearing against multiple later runs.

INSTALL
1. KEEP your real config/strava.php.
2. Copy/overwrite this package into C:\xampp\htdocs\tapsole\
3. Import v033_matching_fix.sql ONCE.
4. Open http://localhost/tapsole/strava/dev_test.php
5. Select ONE existing Run (0.28 km is fine).
6. Select a TapSole shoe whose linked Strava gear is DIFFERENT from the activity's current gear.
7. Click SIMULATE TAP -10 MIN.
8. Open http://localhost/tapsole/strava/match.php

EXPECTED
- Exactly one candidate for that simulated tap.
- Gap = 10 min.
- The same selection must NOT also appear for the next Run.

ONLY after those checks pass:
- APPLY TO STRAVA
- verify the activity gear changed
- use DEV Test -> RESTORE ORIGINAL GEAR
