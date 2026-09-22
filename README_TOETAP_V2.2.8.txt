TOETAP V2.2.8 — TAG CLAIM OWNERSHIP FIX

Built from V2.2.7.

Fixes the factory-issued tag claim path in shoe_add.php:
- NEW admin-issued tags start with owner_user_id = NULL.
- On successful shoe activation, the tag now atomically claims the logged-in user:
  owner_user_id = current user
  user_shoe_id = new physical shoe
  activation_status = ACTIVE
  activated_at = UTC timestamp
- The UPDATE only succeeds while the tag is still NEW and unowned.
- Existing transaction + rowCount protection remains.

No SQL migration.
No UI changes.
No Strava/webhook/Latest Tap Wins logic changes.
