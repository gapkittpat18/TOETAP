TOETAP V2.1.9 — PERSONAL SHOE PROFILE FIX

Problem:
SHOE PERFORMANCE PROFILE could show valid Strava runs while PERSONAL SHOE PROFILE was absent.
V2.1.8 had created a separate role-fetch path, so the two features could disagree.

Fix:
- PERSONAL SHOE PROFILE now derives its role input from the SAME matched Strava activity
  stream used by the existing Shoe Performance Profile on shoe.php.
- No second independent Strava role scan is required on the shoe detail page.
- If Shoe Performance sees linked-gear runs, role inference receives those same runs.
- Role logic remains DAILY / LONG RUN / SPEED / RACE.
- No SQL migration.
- NFC / Latest Tap Wins / webhook unchanged.
