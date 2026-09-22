TapSole V0.4.3.1 - STRAVA HOST FIX

Fix:
All packaged Strava API calls use:
https://www.strava.com/api/v3

OAuth:
https://www.strava.com/oauth/authorize
https://www.strava.com/oauth/token

Scopes:
read,profile:read_all,activity:read_all,activity:write

INSTALL
Copy the strava folder contents over:
C:\xampp\htdocs\tapsole\strava\

NO SQL.
DO NOT replace config/strava.php.
Keep your existing Client ID / Client Secret local.

THEN
1. Open http://localhost/tapsole/strava/connect.php
2. Re-authorize Strava permissions.
3. Open http://localhost/tapsole/strava/gear_link.php
4. Check whether the newly-created Pro 4 appears, including at 0 km.
