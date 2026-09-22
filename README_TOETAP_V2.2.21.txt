TOETAP V2.2.21 — LOADING UI VISIBILITY FIX

Fixes V2.2.20 loader text appearing without the intended overlay/spinner UI.

Changes:
- Critical loader CSS is now inline in v1_nav.php, so browser-cached assets/v1.css
  cannot leave the loader unstyled.
- Loader appears immediately instead of waiting 180 ms.
- Internal link navigation is delayed ~30 ms after requestAnimationFrame so the browser
  has a chance to paint the overlay before PHP/Strava loading begins.
- Very high z-index + !important on critical overlay rules prevents page-specific CSS
  from hiding it.
- Existing form submit loader remains.
- No SQL, NFC, ownership, Strava, webhook, or matching logic changes.
