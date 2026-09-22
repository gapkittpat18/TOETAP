TOETAP V2.2.15 — TAP OWNERSHIP INTEGRITY

Built from V2.2.14.

Claimed NFC tag integrity:
- tap.php now requires an ACTIVE/claimed tag's owner_user_id to match the linked
  user_shoes.user_id before it can create a shoe selection.
- Inconsistent records fail closed with "Tag Link Error" instead of selecting another
  user's shoe.

Admin inventory:
- joins the linked shoe owner ID.
- ACTIVE records with missing/mismatched tag owner vs shoe owner are visibly marked
  CHECK OWNER.

This does not change the normal public tap experience for valid tags.
No SQL migration.
No Strava/webhook logic changes.
