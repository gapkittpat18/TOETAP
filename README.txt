TapSole Strava Gear Debug

Copy:
strava/gear_debug.php
to:
C:\xampp\htdocs\tapsole\strava\gear_debug.php

Then open:
http://localhost/tapsole/strava/gear_debug.php

READ ONLY: it does not update Strava or the TapSole database.

Send back:
1) Athlete response / shoes count
2) The shoes table
3) If Pro 4 is missing, the relevant 'shoes' portion of Raw athlete JSON.
Do NOT send access tokens or Client Secret.
