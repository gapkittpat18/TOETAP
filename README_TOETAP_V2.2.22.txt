TOETAP V2.2.22 — SMART LOADING UI

Loading UI now appears only when the user is actually waiting.

- Normal/fast navigation starts immediately with no artificial delay.
- Loader is scheduled after 350 ms.
- If the page changes quickly, the loader never appears.
- Slow navigation/API pages still show the TOETAP loading overlay.
- Form submissions use the same delayed behavior.
- NFC tap screen uses the same 350 ms threshold.
- Keeps the reliable inline critical loader CSS from V2.2.21.
- Removes the V2.2.21 30 ms forced navigation delay.

No SQL, NFC ownership, Strava, webhook, or matching logic changes.
