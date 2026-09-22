TOETAP V2.2.5 — SHOE JOURNEY ALIGNMENT FIX

V2.2.4 assumed a fixed 20px parent gutter. The actual shoe.php layout/container geometry
does not make that safe, causing the section to look narrow and left-shifted.

V2.2.5:
- Removes all negative margins and width expansion.
- SHOE JOURNEY is width:100% of the real shoe-page content container.
- Removes the nested 720px max-width/auto-centering layer.
- Same left/right alignment as the rest of shoe.php.
- Keeps the preferred classic vertical milestone UI.
- No SQL / Strava API changes.
