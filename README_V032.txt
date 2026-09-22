TAPSOLE V0.3.2 - UTC TIME FIX

WHY:
Strava start_date is UTC. Previous TapSole selections could be stored in Thailand/local server time.
That caused a ~7 hour mismatch and "No unambiguous run found".

WHAT CHANGED:
- MySQL connection session forced to UTC.
- New NFC selections use UTC_TIMESTAMP().
- Tag activation uses UTC_TIMESTAMP().
- DEV simulated tap uses Strava start_date UTC minus 10 minutes.
- Safe Match compares UTC database strings against UTC Strava start_date.
- Activity timestamps are stored as UTC.

INSTALL:
1. KEEP your current config/strava.php with your real credentials.
2. Copy/overwrite this package into C:\xampp\htdocs\tapsole\
3. Import v032_utc_fix.sql ONCE.
   It deletes only UNUSED development selections so old mixed-time test rows cannot cause ambiguity.
4. Open:
   http://localhost/tapsole/strava/dev_test.php
5. Select the 0.28 km Run.
6. SIMULATE TAP -10 MIN.
7. Open:
   http://localhost/tapsole/strava/match.php

EXPECTED:
The selected run should now appear as one candidate with "10 min before run".

NOTE:
UI can later display Asia/Bangkok/local user time. Internal storage remains UTC.
