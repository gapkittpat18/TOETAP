TOETAP V1.6.1 — OAUTH PHONE REDIRECT FIX
=========================================
Fixes:
- /strava/callback.php no longer redirects a logged-out browser to /strava/login.php.
- It redirects to the real app login: /tapsole/login.php.
- After login, the original OAuth callback URL is preserved.

IMPORTANT FOR PHONE TESTING
If Strava returns to http://localhost/tapsole/... on the PHONE, localhost means the phone itself.
For phone/external testing, config/strava.php redirect_uri must use the same public HTTPS host used to start OAuth,
for example:
https://YOUR-CURRENT-TUNNEL.trycloudflare.com/tapsole/strava/callback.php

The Strava app's Authorization Callback Domain must also allow the callback host.
A temporary Quick Tunnel changes hostname when restarted, so update both when it changes.
For production use one stable TOETAP domain.

No SQL changes.
