TOETAP V1.8.2 — TAP → CHECK → CORRECT
- Physical NFC tap shows CURRENT SHOE and still creates the normal Latest Tap selection.
- If correct: do nothing; user is ready to run.
- If incorrect: logged-in tag owner sees CHANGE SHOE directly on the tap result.
- CHANGE SHOE lists active shoes, updates tag mapping, supersedes the old selection, and immediately creates a corrected selection.
- Redirects back to CURRENT SHOE with confirmation.
- Other accounts cannot edit the tag.
- Public NFC tap remains login-free.
- No SQL migration.
Install: overlay on V1.8.1. No SQL required.
