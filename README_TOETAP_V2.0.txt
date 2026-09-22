TOETAP V2.0 — SHOE INTELLIGENCE
================================

Base: TOETAP V1.9.2

Implemented:
1. Shoe purchase price
   - Optional THB purchase price in Add Shoe and Edit Shoe.
   - Cost/km calculated from current mileage.

2. Shoe lifespan
   - Uses existing target_km.
   - Displays mileage, % used, remaining km and progress.

3. Performance by distance
   - 5K: 4.75–5.25 km
   - 10K: 9.50–10.50 km
   - HM: 20.0–22.2 km
   - Best time, average pace, average HR, best-performance shoe.
   - HR/pace efficiency = metres per minute / average HR. Higher = more speed per heartbeat.
   - HR metrics appear only when HR is available.

4. Shoe performance as mileage increases
   - Mileage bands: 0–100, 100–250, 250–400, 400+ km.
   - Average pace and HR/pace efficiency per mileage band.
   - Uses the loaded activity history and reconstructs cumulative shoe mileage chronologically.

5. Data-source behavior
   - Strava remains primary when connected.
   - Loads up to 600 recent Strava activities for analytics.
   - Without Strava, uses local/manual TOETAP activities.
   - Manual run entry now accepts optional average HR.

Also:
- Add Shoe now has CSRF protection.
- Shoes-page admin + button is guarded consistently.
- Existing NFC, Latest Tap Wins, Strava Gear, webhook and admin provisioning flows remain intact.

SQL REQUIRED:
Run v200_shoe_intelligence.sql once on an existing database.

Important analytics limitation:
The shoe-aging chart is based on the activity history currently available to TOETAP (up to 600 recent Strava activities, or local history). It does not claim data older than the loaded history.
