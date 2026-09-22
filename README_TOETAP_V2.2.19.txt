TOETAP V2.2.19 — SAFE AUTH RESUME

Built from V2.2.18.

- Hardened safeNext(): rejects external/scheme URLs, control characters, backslashes,
  traversal segments, and absolute paths outside /tapsole.
- Preserves valid local NFC activation destinations such as:
  /tapsole/shoe_add.php?t=<token>
- Create Account now preserves the same `next` destination from Sign In.
- A new customer who taps an unclaimed NFC while logged out can create an account and
  return directly to the tag activation flow instead of losing the tag and landing elsewhere.
- Normal registration without an NFC/auth resume destination still goes to onboarding.php.
- Sign In <-> Create Account links preserve `next` in both directions.

No SQL migration.
No NFC rewrite.
No Strava/webhook changes.
