TOETAP V2.0.9 — PERFORMANCE VS SHOE AGE

This release promotes the existing shoe-aging analytics into a clearer product feature.

Changes:
- Renamed section to PERFORMANCE VS SHOE AGE.
- Mileage stages are now:
  FRESH · 0–100 km
  BROKEN IN · 100–250 km
  AGED · 250–400 km
  HIGH MILEAGE · 400+ km
- Keeps pace / HR-efficiency trend analysis already present in Insights.
- Adds an explicit “Trend · not causation” explanation because pace changes can also be caused by fitness, route, weather, workout type, etc.
- Shoe Performance Profile now links directly to the aging analysis.
- No SQL migration required.
- No changes to NFC, Latest Tap Wins, webhook or Strava gear assignment.

Note:
This version improves presentation/interpretation of the existing aging model. A later version can make the aging analysis fully shoe-selectable and reconstruct historical shoe mileage more precisely.
