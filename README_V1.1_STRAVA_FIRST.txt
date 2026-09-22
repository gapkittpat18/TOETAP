TapSole V1.1 — STRAVA FIRST / STRAVA OPTIONAL

This corrects the previous V1.1 behavior.

CONNECTED TO STRAVA:
- Home continues using live Strava activity data.
- Selected shoe mileage continues using Strava gear.
- Insights uses live Strava by default.
- Existing webhook matching + Strava gear update remain unchanged.

NO STRAVA:
- TapSole still works for shoes, tags and NFC.
- Manual Add Run remains available.
- Generic activity source fields remain useful for fallback/manual data.

INSTALL:
1. Overlay this ZIP on the current TapSole folder.
2. Run v110_standalone.sql once if you have not already run it.
3. If you already ran that SQL, do NOT run it again.
