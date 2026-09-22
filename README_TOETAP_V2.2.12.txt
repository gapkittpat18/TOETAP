TOETAP V2.2.12 — AUTH / CSRF HARDENING

Built from V2.2.11.

Added CSRF protection to browser POST forms that were missing it:
- login.php
- register.php
- strava/match.php

Webhook setup hardening:
- webhook/setup.php now requires ADMIN, not merely any logged-in user.
- webhook migration POST now requires CSRF.
- Strava's public webhook callback is intentionally NOT given CSRF protection because it is
  called server-to-server by Strava, not submitted from a TOETAP browser session.

No SQL migration.
No NFC flow change.
No Latest Tap Wins change.
No webhook callback behavior change.
