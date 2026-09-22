TOETAP V2.2.18 — NEW TAG ACTIVATION FIX

Fixes the V2.2.17 regression.

Rule:
- activation_status=NEW means the physical tag is available to be claimed.
- The customer who possesses/taps that NEW tag may activate it.
- Activation atomically assigns owner_user_id and user_shoe_id to that customer and changes
  the tag to ACTIVE.
- We no longer reject a NEW tag merely because an old/prototype DB record has stale
  owner_user_id or user_shoe_id values.
- ACTIVE tags remain protected by ownership rules.

New factory-issued tags still correctly start with owner_user_id=NULL and user_shoe_id=NULL.
No SQL migration.
No NFC rewrite.
No Strava/webhook changes.
