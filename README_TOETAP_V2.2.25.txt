TOETAP V2.2.25 — HOME PHOTO COLOR ROOT FIX

V2.2.24 disabled grayscale on the IMG itself, but Home remained black & white
because the grayscale filter is actually applied to the parent .shoeShape.

CSS:
.shoeShape { filter: grayscale(1); }

A CSS filter on a parent affects the complete rendered subtree, so filter:none on
the child image cannot undo it.

V2.2.25:
- Removes the parent grayscale filter when it contains an uploaded custom shoe photo.
- Adds a small JS fallback that clears the parent filter directly.
- Keeps the legacy grayscale styling when the default TOETAP sneaker artwork is used.
- No SQL changes.
- No upload/NFC/Strava/webhook/ownership changes.
