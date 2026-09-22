TOETAP V2.2.24 — HOME CUSTOM PHOTO COLOR FIX

Fixes custom uploaded shoe photos appearing black & white on Home.
The legacy Home sneaker class applies a monochrome filter; custom photos now
explicitly bypass that treatment while the original fallback artwork is unchanged.

No SQL changes. No upload/NFC/Strava/webhook/ownership logic changes.
