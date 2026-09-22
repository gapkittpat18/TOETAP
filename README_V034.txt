TapSole V0.3.4 - APPLY SQL FIX

Fix:
SQLSTATE[HY093]: Invalid parameter number

Cause:
The activities INSERT statement had 9 SQL placeholders, but match.php supplied only 8 parameters.
shoe_selection_id was missing from the execute() parameter array.

Install:
1. Replace only:
   C:\xampp\htdocs\tapsole\strava\match.php
   with the match.php from this package.
2. No SQL import is required.
3. Reload Safe Run Match.
4. The simulated selection should still be unused because the failed SQL transaction path did not reach the used=1 update.
5. Press APPLY TO STRAVA again.

Note:
The Strava PUT occurs before the failing SQL INSERT in V0.3.3. Therefore Strava may already have changed the activity gear even though TapSole displayed the SQL error. Reload the Safe Run Match first and check Current Strava gear before pressing APPLY again.
