TOETAP V2.1.6 — COMPARE SHARED CSS FIX

Actual root cause:
- v1_nav.php only contains the navigation HTML.
- The real navigation styling is in assets/v1.css.
- compare.php was not loading assets/v1.css, so v1nav() rendered as plain links.

Fix:
- compare.php now loads assets/v1.css exactly like the existing TOETAP pages.
- Uses the normal .app page shell and top/profile header.
- Keeps compare-specific CSS scoped under .comparePage.
- Shared bottom nav now uses the same .nav rules as Home / Shoes / Insights / Tags.
- SHOES remains active.
- No SQL migration.
