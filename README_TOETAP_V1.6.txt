TOETAP V1.6 — STRAVA MULTI-USER
================================
Built from the user's full working tapsole folder.

WHAT IS FIXED
- Strava OAuth requires a logged-in TOETAP user.
- OAuth state is bound to that exact TOETAP user and expires after 15 minutes.
- Callback stores tokens against the logged-in user, never hard-coded user 1.
- A Strava athlete cannot be attached to two TOETAP accounts.
- validToken/getConnection no longer silently default to user 1.
- Legacy Strava status/match/gear-link pages are session-user scoped.
- Webhook continues routing owner_id -> strava_connections.athlete_id -> user_id.
- Webhook matching remains scoped to that user's shoe selections and shoes.
- Activity idempotency is user/source aware.
- Strava webhook writes source=STRAVA + external_activity_id.
- Existing Latest Tap Wins / 6-hour window / automatic Strava gear update preserved.
- Public NFC tap remains public; new-tag setup naturally requires login when it reaches account pages.

INSTALL
1. Back up current folder + DB.
2. Overlay this FULL package on the current /tapsole folder.
3. Run v160_strava_multiuser.sql once.
4. Existing logged-in test account: reconnect Strava once so the connection is confirmed under that account.
5. Test with Account A before adding Account B.
6. Then create Account B and connect a DIFFERENT Strava account.
7. Verify each account sees only its own shoes/runs.

IMPORTANT
- Keep current /tapsole path and tapsole_v0 DB for compatibility.
- Do not expose config/strava.php.
- setup_existing_account.php should be deleted after the existing account password is set.
