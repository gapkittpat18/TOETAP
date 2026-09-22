TOETAP V2.0.8 — SHOE PERFORMANCE + PR SHOE HISTORY

1. Shoe Performance Profile
- Added directly to each Shoe detail page.
- For a Strava-linked shoe: runs, distance, average pace.
- 5K / 10K / 21K / 42K cards.
- Exact PR on that shoe uses Strava DetailedActivity.best_efforts.
- If no exact PR is found, the card labels the whole-activity metric as BEST, not PR.
- Cached in PHP session for 6 hours.
- Scans up to 1,000 recent Strava activities and detail-fetches at most 50 PR-candidate activities per shoe.

2. PR Shoe History
- Added to Insights.
- Shows all-time 5K / 10K / 21K / 42K PR, shoe used, and date.
- Shoe comes from the gear_id on the same DetailedActivity that supplied the Strava Best Effort.
- Unmapped Strava gear is explicitly shown as Unlinked Strava shoe.

3. Safety cleanup
- Added CSRF validation to Retire Shoe POST.

No SQL migration required.
