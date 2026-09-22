TOETAP V2.2.14 — OWNERSHIP AUDIT

Built from V2.2.13.

Security/data-integrity fixes:
1. reset_demo.php is now ADMIN-only.
   The old development utility could delete shoe selections and activities globally if its URL
   was opened. It is retained for development, but normal users can no longer execute it.

2. strava/match.php now validates the shoe selection and ownership BEFORE sending the PUT
   request to Strava.
   - selection must belong to the logged-in user
   - linked physical shoe must belong to the same user
   - submitted gear_id must match that selection's stored Strava gear
   - marking a selection used is also scoped by user_id

No SQL migration.
No NFC flow change.
No automatic webhook matching change.
