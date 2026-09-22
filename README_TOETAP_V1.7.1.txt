TOETAP V1.7.1 — STRAVA WEBHOOK AUTH FIX
========================================
Fix:
V1.7's generic Push Subscription helper did not send client_id/client_secret
at all on DELETE requests. Migration first lists the old subscription and then
deletes it, so the DELETE step could fail with:
401 Authorization Error / Application invalid.

V1.7.1 sends:
- GET: client_id + client_secret in query string
- DELETE: client_id + client_secret in query string
- POST create: client_id + client_secret + callback_url + verify_token
  as application/x-www-form-urlencoded

No SQL changes.
No OAuth reconnect required.

After overlay:
1. Login.
2. Open https://app.toetap.run/tapsole/webhook/setup.php
3. Press MIGRATE WEBHOOK TO APP.TOETAP.RUN once.
4. Confirm a Strava subscription ID appears.
