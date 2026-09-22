TOETAP V2.2.13 — ACCOUNT SESSION HARDENING

Built from V2.2.12.

Changes:
- Logout is now POST-only instead of a state-changing GET link.
- Logout requires the existing CSRF token.
- Settings keeps the same LOG OUT button UX, but submits a protected POST form.
- Successful login/register session establishment now rotates the CSRF token after
  session_regenerate_id(true).

Why:
A third-party page/image/link should not be able to log a TOETAP user out just by requesting
logout.php.

No SQL migration.
No Strava/NFC/webhook/Latest Tap Wins changes.
