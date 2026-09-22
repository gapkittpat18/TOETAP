TapSole V0.4.3 - Strava Gear Permission Fix

WHY:
Your GET /athlete response had resource_state=2 and no shoes[].
Strava documents that tokens with profile:read_all receive DetailedAthlete;
other tokens receive SummaryAthlete. DetailedAthlete contains shoes[].

INSTALL:
Copy BOTH files into:
C:\xampp\htdocs\tapsole\strava\
- connect.php
- gear_link.php

NO SQL.
DO NOT replace config/strava.php.
DO NOT share Client Secret.

THEN:
1. Open http://localhost/tapsole/strava/connect.php
2. Strava authorization appears again because approval_prompt=force.
3. Approve permissions.
4. After returning to TapSole, open:
   http://localhost/tapsole/strava/gear_link.php
5. The newly-created 0 km Pro 4 should now appear.
6. Link TapSole Pro 4 to that Strava gear.

Requested OAuth scopes:
read,profile:read_all,activity:read_all,activity:write

IMPORTANT API LIMIT:
The current public Strava API documents GET /gear/{id}, but no endpoint to
create/list/update/delete gear directly. Therefore TapSole cannot create a
new Strava shoe through the documented public API. The practical V1 flow is:
- TapSole can automatically read all Strava shoes after profile:read_all.
- If matching shoe exists -> auto-link/confirm.
- If it does not exist -> user must create it in Strava, then return/retry.
