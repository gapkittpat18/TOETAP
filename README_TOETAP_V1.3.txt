TOETAP V1.2 — STRAVA FIRST + REAL FALLBACK
============================================

Core rule:
- Strava connected and usable -> Strava is the default source everywhere.
- No Strava / unavailable -> TOETAP automatically falls back to local MANUAL activities.
- User never needs a Strava account to use TOETAP.

Home:
- Recent runs: Strava first, TOETAP manual fallback.
- 7/30 day stats: Strava first, TOETAP manual fallback.
- Selected shoe mileage: Strava gear first, manual mileage fallback.
- Shows current DATA SOURCE.

Insights:
- Live Strava by default.
- Automatically uses TOETAP manual runs if Strava is not connected.
- Shows current DATA SOURCE.

Existing NFC / Latest Tap Wins / webhook / Strava gear update are unchanged.

SQL:
- Keep v110_standalone.sql from V1.1. If already run, do not run again.
