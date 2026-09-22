TOETAP V2.2.20 — LOADING UI

Adds visible loading feedback for slow navigation and form submissions.
- Global TOETAP loading overlay on shared V1 pages.
- Appears after 180ms to avoid flashing on fast pages.
- Shows LOADING for navigation and SAVING for form submissions.
- Automatically clears on browser back/forward restore.
- External/new-tab links do not trigger it.
- NFC tap screen also gets a dark loading overlay when continuing into the app.
- No SQL, NFC, ownership, Strava API, webhook, or matching logic changes.

Note: PHP pages that spend time on server/API work before sending their first HTML byte
cannot show an in-page loader on a direct hard refresh. This patch covers normal app
navigation, which is where users need immediate feedback most often.
