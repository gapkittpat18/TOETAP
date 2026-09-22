TOETAP V2.2.17 — FACTORY TAG / NO OWNER

Built from V2.2.16.

Correct lifecycle:
1. Admin generates URL and writes it to NFC.
2. The generated tag remains factory-issued NEW / UNCLAIMED:
   owner_user_id = NULL
   user_shoe_id = NULL
3. Admin account is NOT the owner merely because it generated the URL.
4. Customer taps the physical NFC.
5. Customer signs in (if needed), adds/selects shoe, and activation atomically claims
   the tag to that customer's user ID.
6. Only after customer activation does the tag become ACTIVE and owned.

Second-hand release from V2.2.16 still returns the same physical tag to NEW / UNCLAIMED
while preserving tag_code and public_token.

Added an invariant check so shoe_add.php refuses malformed NEW records that already contain
an owner or shoe, rather than silently producing confusing activation failures.

No SQL migration.
No NFC rewrite required for existing correctly provisioned NEW tags.
No Strava/webhook changes.
