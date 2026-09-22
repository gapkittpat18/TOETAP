TOETAP V2.2.9 — ADMIN TAG INVENTORY

Built from V2.2.8.

Adds admin_tags.php:
- Admin-only inventory of factory-issued TOETAP tags.
- Counts Issued / Unclaimed / Claimed.
- Filters ALL / NEW / ACTIVE.
- Shows tag code, owner, physical shoe, activation time, and permanent NFC URL.
- Existing customer Tags page remains owner-scoped.
- Admin gets a small TAG INVENTORY action on Tags page.
- No edit/delete/reassign controls are added, avoiding accidental production-tag mutation.

No SQL migration.
No Strava API calls.
No NFC URL format changes.
No webhook / Latest Tap Wins changes.
