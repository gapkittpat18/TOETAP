TOETAP V2.0.6 — PERFORMANCE PERIOD

Performance by Distance now has:
3M | 6M | 1Y | ALL

Default: 1Y.

The selected period filters the activities used for 5K / 10K / 21K / 42K performance cards.
For Strava, pagination stops after reaching activities older than the selected cutoff.
ALL is capped at 2,000 loaded activities to avoid unbounded API usage.
Local/manual fallback uses the same 3M/6M/1Y period logic and a 2,000-row cap.

No SQL changes required.
