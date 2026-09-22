TAPSOLE V0.4.2 - REAL WEBHOOK AUTO PROCESSOR

Copy these files over your existing V0.4.1 installation.
NO SQL import.
KEEP your existing config/strava.php and existing webhook subscription.

Files:
webhook/callback.php
webhook/processor.php
webhook/process.php
webhook/events.php

REAL FLOW:
NFC Tap -> unused selection
Run syncs to Strava
-> Strava CREATE webhook POST
-> callback stores event
-> callback auto-processes it
-> fetch activity
-> exactly one unused selection in previous 2 hours
-> PUT mapped gear to Strava
-> store activity
-> mark selection used
-> webhook event APPLIED

No Events page and no PROCESS click are required for a real webhook.

IMPORTANT LOCAL TEST CONDITIONS:
- Apache + MySQL must be running.
- cloudflared tunnel PowerShell must remain running.
- The temporary trycloudflare URL must remain the SAME URL that was registered with the current Strava webhook subscription.
- PC must remain awake and online.
- If the quick tunnel URL changes, recreate/update the Strava subscription using webhook/setup.php.

events.php remains available as an audit/debug page.
process.php remains as a manual recovery tool for RECEIVED events.

NEXT REAL TEST:
1. Keep tunnel/XAMPP running.
2. Tap the registered TapSole tag before a real run.
3. Run and sync to Strava.
4. Do NOT open TapSole or click Process.
5. Check Strava gear and webhook/events.php afterward.
Expected webhook status: APPLIED.

DEV NOTE:
dev_simulator.php creates a database event directly; it does not POST through the public callback.
Use it only for processor tests. The next meaningful test is a real Strava-created activity.
