TapSole V0.9 — Analytics / Insights

INSTALL
1. Copy:
   analytics.php
   install_v090.php
   assets/analytics.css
   into C:\xampp\htdocs\tapsole\
2. Open:
   /tapsole/install_v090.php
   once.
3. After it says success, delete install_v090.php.
4. No SQL migration.

WHAT IT ADDS
- 30-day distance
- 30-day run count
- average moving pace
- 8-week distance chart
- shoe rotation ranking by distance
- runs and average pace by shoe
- previous 30-day volume comparison
- Home link/card to Insights

Data is read from live Strava activity history, while shoe names come from existing TapSole mappings.
No change to NFC, Latest Tap Wins, matching, webhook, onboarding, or Strava write logic.

IMPORTANT V1 DIRECTION
This is descriptive analytics only. It deliberately avoids inventing a shoe performance score from small/non-comparable samples.
Later versions can add comparable-run analytics using distance/pace/HR/elevation filters.
