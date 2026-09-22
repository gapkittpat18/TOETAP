TOETAP V1.8.4 — FULL SCHEMA NAME AUDIT
=========================================
Problem:
V1.8 introduced an accidental database-column rename:
  issued_by_toetap  (WRONG / does not exist)
instead of the existing technical schema:
  issued_by_tapsole (CORRECT)

Files corrected:
- tag_new.php
- shoe_add.php

Audit result:
- Remaining issued_by_toetap references: 0
- PHP files linted: 45
- PHP syntax errors: 0
- Existing DB name tapsole_v0 intentionally preserved.
- Existing DB column issued_by_tapsole intentionally preserved.
- UI/product branding can remain TOETAP; technical schema identifiers are NOT renamed.

No SQL migration required for this fix.
Overlay V1.8.4 on V1.8.3.
