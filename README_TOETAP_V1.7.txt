TOETAP V1.7 — STABLE DOMAIN + STRAVA WEBHOOK
============================================
Permanent public base:
https://app.toetap.run/tapsole/

Changes
- Strava OAuth callback is fixed to:
  https://app.toetap.run/tapsole/strava/callback.php
- Webhook migration page is authenticated.
- Stable webhook callback is fixed to:
  https://app.toetap.run/tapsole/webhook/callback.php
- Migration page lists Strava's current subscription, deletes the old subscription,
  stores a fresh verify token, then creates the stable subscription.
- Webhook event viewer now requires TOETAP login.
- Core multi-user owner_id -> athlete_id -> user_id processing remains unchanged.
- No SQL migration required.

INSTALL / MIGRATE
1. Overlay this package on current /tapsole/.
2. No SQL to run.
3. Confirm Cloudflare Tunnel app.toetap.run -> http://localhost:80 is Healthy.
4. Login to TOETAP.
5. Open:
   https://app.toetap.run/tapsole/webhook/setup.php
6. Press MIGRATE WEBHOOK TO APP.TOETAP.RUN once.
7. Page should show a Strava subscription ID.
8. Test a real run after an NFC tap.
9. Open webhook/events.php and confirm CREATE event becomes APPLIED.
10. Confirm Strava activity gear changed to the tapped shoe.

Only after this E2E passes should existing NFC URLs be rewritten from trycloudflare.com
to https://app.toetap.run/tapsole/tap.php?tag=...
