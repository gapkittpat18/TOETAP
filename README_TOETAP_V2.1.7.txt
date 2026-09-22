TOETAP V2.1.7 — SHOE BEST EFFORT FIX

Problem:
A 10.3 km Metaspeed run could show no 10K result because Compare only fetched
DetailedActivity.best_efforts when the Strava summary had pr_count > 0.

Fix:
- Any shoe run >= 4.95 km can now be inspected for Strava Best Efforts.
- Comparison metric is correctly named BEST EFFORT, not PR.
- 5K / 10K / 21K / 42K use the fastest Strava Best Effort found for that physical shoe.
- PR badge is only shown when that effort matches the existing all-time Strava PR cache from Insights.
- Detail scan cap increased from 50 to 100 candidate shoe activities.
- Compare cache key bumped to v217 so old cached PR-only data is not reused.
- No SQL migration.
- No NFC / webhook / Latest Tap Wins changes.

Limit:
For a shoe with more than 100 eligible activities, older activities beyond the detail cap
may still be omitted. Durable best-effort caching can remove this API/cap limitation later.
