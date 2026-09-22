TAPSOLE V0.2 - STRAVA

1) BACK UP your current tapsole folder.
2) Extract this ZIP and copy/overwrite its contents into:
   C:\xampp\htdocs\tapsole\

3) Edit:
   C:\xampp\htdocs\tapsole\config\strava.php

Replace:
   PUT_YOUR_CLIENT_ID_HERE
   PUT_YOUR_CLIENT_SECRET_HERE

Do NOT send/share your Client Secret.

4) In your Strava API settings make sure:
   Authorization Callback Domain = localhost

5) XAMPP: Apache + MySQL must be running.
   PHP cURL extension must be enabled.

6) Open:
   http://localhost/tapsole/strava/status.php

7) Click CONNECT STRAVA and authorize TapSole.

Expected:
   ✓ STRAVA CONNECTED
   athlete name/id
   latest 10 activities
   gear_id for each activity when present

NOTE:
This package DOES NOT auto-change Strava gear yet.
First we verify OAuth/read access safely. Auto-assignment comes next.
