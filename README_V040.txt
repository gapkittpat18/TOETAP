TAPSOLE V0.4 - STRAVA WEBHOOK AUTO MATCH

IMPORTANT
- Keep your real config/strava.php.
- Import v040_webhook.sql once.
- DEV/local only. Do not expose phpMyAdmin or your whole XAMPP setup publicly.
- callback.php acknowledges events quickly and stores them.
- process.php performs Strava API work separately.

LOCAL TEST WITH HTTPS TUNNEL
1. Start Apache + MySQL.
2. Start an HTTPS tunnel to your local Apache port (normally 80).
3. Example public base: https://abc.example-tunnel.com
4. Open locally:
   http://localhost/tapsole/webhook/setup.php
5. Callback must be:
   https://abc.example-tunnel.com/tapsole/webhook/callback.php
6. Make your own random verify token (12+ chars).
7. Create subscription.

TEST CALLBACK FIRST
Open:
https://YOUR-TUNNEL/tapsole/webhook/callback.php?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=TEST123
Expected JSON:
{"hub.challenge":"TEST123"}

AUTOMATION FLOW
NFC tap -> shoe_selection unused
Strava creates activity -> webhook callback stores RECEIVED event
process.php -> fetches activity -> finds exactly one unused selection in prior 2h
-> updates Strava gear -> records activity -> marks selection used.

FOR V0.4 LOCAL TEST
Because XAMPP has no background worker, callback stores the event immediately, then you can:
- open webhook/events.php
- click PROCESS RECEIVED EVENTS NOW
This tests the webhook event path without polling Strava.
V0.4.1 can add Windows Task Scheduler/cron or a tiny local worker to process automatically.
On cloud production, use a queue/worker.

SAFETY
- activity create only
- Run / TrailRun / VirtualRun only
- exactly one eligible unused selection required
- max 2h
- already processed activity ignored
- non-run events ignored
