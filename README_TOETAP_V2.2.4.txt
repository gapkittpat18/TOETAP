TOETAP V2.2.4 — SHOE JOURNEY WIDTH FIX

Cause:
V2.2.3 used width:100vw with left:50% / margin:-50vw. On mobile browsers 100vw can
include viewport/scrollbar/safe-area behavior beyond the actual TOETAP app shell,
making the section look wider than the page.

Fix:
- Removed 100vw viewport breakout.
- Uses the known TOETAP content gutter: width calc(100% + 40px), margin -20px.
- This cancels the page's 20px left/right padding without exceeding the app shell.
- overflow:hidden added as an extra guard.
- Classic vertical UI retained.
- No SQL / API changes.
