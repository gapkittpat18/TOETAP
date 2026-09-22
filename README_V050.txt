TapSole V0.5 — Latest Tap Wins

WHAT CHANGED
1. tap.php
   - Every successful registered NFC tap supersedes all older unused taps for that user.
   - The newest tap remains used=0 and becomes the active shoe selection.
   - Multiple accidental/repeated taps no longer create ambiguous matching.

2. webhook/processor.php
   - Matching window changed from 2 hours to 6 hours.
   - Processor chooses the newest eligible unused selection (ORDER BY selected_at DESC, id DESC LIMIT 1).
   - After a successful Strava assignment, that selection is consumed as before.

3. v050_latest_tap_wins.sql
   - No schema change.
   - One-time cleanup marks old V0.4 unused test selections as used.

INSTALL
A. Backup your current C:\xampp\htdocs\tapsole folder.
B. Run v050_latest_tap_wins.sql once in tapsole_v0.
C. Copy tap.php over C:\xampp\htdocs\tapsole\tap.php
D. Copy webhook\processor.php over C:\xampp\htdocs\tapsole\webhook\processor.php
E. Do NOT replace config\strava.php or config\database.php.

TEST
1. Tap NFC TS000001 once. Ready to Run should appear.
2. Tap it again. This is OK; only the newest selection remains active.
3. Run/sync within 6 hours.
4. Do not manually process the webhook.
5. Confirm Strava automatically shows the mapped TapSole shoe and webhook event is APPLIED.

EXPECTED LOGIC
Tap A -> Tap B -> Run = B
Tap A -> Tap A again -> Run = latest A
Tap -> Run after >6h = no automatic assignment
