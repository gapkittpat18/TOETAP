TOETAP V2.1.8 — ROTATION INTELLIGENCE

New:
- YOUR ROTATION section on Shoes.
- PERSONAL SHOE PROFILE on shoe detail.
- Suggested roles: DAILY, LONG RUN, SPEED, RACE.
- Inference uses the user's own Strava-linked shoe activity summaries:
  distance, pace distribution, usage frequency, and Strava PR signals.
- Up to 600 recent activities are scanned; per-shoe profile cache is 6 hours.
- Race classification is conservative: pace alone cannot classify a shoe as RACE.
- This is a usage profile, not an objective shoe review/rating.
- No SQL migration.
- NFC / Latest Tap Wins / webhook unchanged.

Important:
Role inference is heuristic and becomes more meaningful as the user accumulates runs.
