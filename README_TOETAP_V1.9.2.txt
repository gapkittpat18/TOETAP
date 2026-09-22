TOETAP V1.9.2 — ADMIN TAG PROVISIONING
======================================

Base: TOETAP V1.9.1

Changes:
- NFC tag creation is now admin-only.
- Normal users no longer see the + Add Tag button.
- Direct access to tag_new.php requires an admin account.
- Admin provisioning creates only NEW / unclaimed tags:
  owner_user_id = NULL
  user_shoe_id = NULL
  activation_status = NEW
  issued_by_tapsole = 1
- Customer activation/claim remains through the permanent opaque ?t= URL.
- Existing owned tags remain visible/manageable by their owners.
- Existing NFC, Latest Tap Wins, Strava, webhook and gear flows are preserved.
- v180 migration included in this package now uses UUID-based token backfill for MariaDB/XAMPP compatibility.

SQL REQUIRED:
1) Run v192_admin_tag_provisioning.sql.
2) Promote ONLY your own TOETAP account:
   UPDATE users SET is_admin=1 WHERE email='YOUR_REAL_TOETAP_EMAIL';

Do not leave every account as admin.
