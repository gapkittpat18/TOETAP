TOETAP V2.0.1 — ANALYTICS + SHOE LIFE UI FIX

Base: TOETAP V2.0

Performance:
- BEST TIME, BEST PACE and BEST RUN DISTANCE now come from the same activity.
- AVG PACE remains the average across all runs in that distance bucket and is clearly separate.
- Best shoe now shows "Not recorded" rather than "No shoe" when the best activity has no mapped gear.
- Average HR and HR/pace efficiency use clearer "No HR data" wording.

Shoe Life UI:
- Replaced the plain lifespan/cost rows with product-style dark shoe cards.
- Each card shows lifespan percentage, visual progress bar, used km, remaining km and cost/km.
- Percentage uses one decimal so very new shoes no longer misleadingly show 0%.
- If purchase price is missing, UI says "Add price" / "Tap to add purchase price" instead of a dash.
- Cards open the shoe detail page.

No SQL migration required beyond the V2.0 migration if it has already been run.
