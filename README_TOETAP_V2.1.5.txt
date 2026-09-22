TOETAP V2.1.5 — COMPARE SHARED NAV UI FIX

Fix:
- Compare now renders the existing shared v1nav('shoes') outside the compare page content wrapper.
- Compare-specific generic CSS is scoped under .comparePage so it cannot restyle the shared bottom navigation.
- SHOES remains the active navigation item because Compare is a Shoes sub-page.
- No new navigation item was added.
- No SQL migration.
- No NFC / webhook / Latest Tap Wins changes.
