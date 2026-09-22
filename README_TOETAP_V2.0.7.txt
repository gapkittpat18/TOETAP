TOETAP V2.0.7 — STRAVA PR / BEST EFFORT

Fix:
The old BEST value was the shortest moving_time among whole activities near 5K/10K/21K/42K.
That is NOT a true PR.

Now:
- ALL-TIME PR is derived from Strava DetailedActivity.best_efforts exact-distance efforts.
- 5K / 10K / 21K / 42K tabs show PR from Strava Best Efforts.
- 3M / 6M / 1Y / ALL does NOT change PR.
- Period selector affects only matching-activity analytics (run count, average pace, best matching activity).
- Old BEST TIME wording changed to BEST ACTIVITY so it cannot be confused with PR.
- PR results are cached in PHP session for 6 hours to reduce Strava API usage.
- PR sync scans up to 2,000 activity summaries and requests details only for activities Strava flags with pr_count > 0.
- Detail requests are safety-capped at 70 candidates per sync to protect read-rate quota.
- No SQL migration required.

Important:
If a Strava account has more than 70 PR-achievement activities inside the scanned history, the first sync is safety-capped rather than risking the API rate limit.
