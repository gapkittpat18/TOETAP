TOETAP V2.1.0 — SHOE COMPARISON

New compare.php:
- Select any two active shoes.
- Compare runs, distance, average pace, average HR.
- Compare exact 5K / 10K / 21K / 42K PRs achieved on each shoe using Strava Best Efforts.
- Compare target lifespan and Value Unlocked %.
- Uses the existing Strava-first architecture.
- Falls back to local TOETAP activities for basic usage metrics if no Strava gear is linked.
- Caches per-shoe Strava comparison data for 6 hours.
- No review/rating score and no causal claim that a shoe produced better performance.
- Shoes page includes a COMPARE entry.
- No SQL migration required.
