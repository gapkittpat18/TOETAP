TOETAP V2.1.2 — COMPARE NAV FIX

Fix:
- compare.php called a non-existent v1BottomNav() function.
- Replaced it with the actual shared navigation function: v1nav('shoes').
- No SQL migration.
- No changes to comparison logic, NFC, webhook, or Latest Tap Wins.
