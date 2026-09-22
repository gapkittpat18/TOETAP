TOETAP V2.2.10 — LOGOUT UI

Built from V2.2.9.

The logout backend already existed at /tapsole/logout.php, but there was no visible UI control.
This patch adds a LOG OUT button to Settings, directly below STRAVA (OPTIONAL).

Flow:
Profile/avatar -> Settings -> LOG OUT -> existing logoutUser() -> login.php

No SQL migration.
No auth logic rewrite.
No Strava/NFC/webhook changes.
