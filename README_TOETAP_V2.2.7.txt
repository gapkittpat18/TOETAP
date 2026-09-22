TOETAP V2.2.7 — STRAVA-FIRST SHOES PAGE

Built strictly from V2.2.6 baseline.

Changes:
- For a shoe linked to Strava gear, Shoes page mileage now uses Strava gear.distance.
- Lifespan %, remaining km, and Value Unlocked are therefore calculated from the same
  Strava-first mileage source.
- If Strava is unavailable, a gear lookup fails, or the shoe has no Strava gear,
  the existing TOETAP fallback remains: initial_km + local/manual activity km.
- Linked Strava mileage is cached in the PHP session for 6 hours.
- Duplicate physical shoes are not merged or altered.
- Small "RUN · STRAVA" source label appears when Strava mileage is actually being used.
- No SQL migration.
- No NFC / Latest Tap Wins / webhook changes.

API behavior:
On a cold cache, one GET /gear/{id} is made for each unique linked Strava gear on the active
Shoes page. Normal reloads during the next 6 hours use session cache.
