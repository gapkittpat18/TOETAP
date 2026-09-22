TOETAP V2.2.16 — TAG RELEASE / TRANSFER

Built from V2.2.15.

Second-hand hardware lifecycle:
- Current owner can open Manage Tag and choose RELEASE TAG.
- Release is POST + CSRF protected.
- Ownership is re-checked and row-locked inside a DB transaction.
- Pending unused selections created by that tag for the old owner are superseded.
- Tag is reset to:
    owner_user_id = NULL
    user_shoe_id = NULL
    activation_status = NEW
    activated_at = NULL
- tag_code and public_token are intentionally preserved.
  The physical NFC does NOT need to be rewritten.
- Old owner's shoe, activities, mileage and history are not deleted or transferred.
- New owner taps the same physical tag and uses the existing NEW-tag activation/claim flow.
- Only current owner can release it from this page; admin inventory remains separate.

No SQL migration.
No Strava/webhook changes.
