TAPSOLE V0.4.1 - DEV WEBHOOK SIMULATOR

Copy these files into your existing V0.4 TapSole installation.
No SQL import required.

Open:
http://localhost/tapsole/webhook/dev_simulator.php

Test:
1. Choose an existing short Run (0.28 km recommended).
2. Choose a TapSole shoe whose mapped gear differs from the activity's current gear.
3. CREATE DEV WEBHOOK EVENT.
   This creates:
   - a simulated NFC selection exactly 10 minutes before the run
   - a RECEIVED webhook event
4. Open Webhook Events.
5. Confirm provider=strava_dev and status=RECEIVED.
6. Click PROCESS RECEIVED EVENTS NOW.
7. Expected JSON status=APPLIED.
8. Return to Events: status should be APPLIED.
9. Check Strava: activity gear should have changed.

This simulator intentionally reuses an existing activity and can change its Strava gear.
It deletes the prior TapSole activity record for that chosen activity so the pipeline can be tested again.
Do not deploy dev_simulator.php to production.

Real Strava webhook subscription remains unchanged.
