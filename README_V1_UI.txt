TapSole V1 — Mockup UI

This is deliberately installed SIDE-BY-SIDE with the working app first.

Copy:
- index_v1.php
- shoes_v1.php
- tags_v1.php
- v1_nav.php
- assets/v1.css

Then open:
  /tapsole/index_v1.php

It uses your REAL current database + Strava data.
It does NOT change NFC matching, webhook, database schema, onboarding, or the current index.php.

Visual direction:
- light editorial app shell
- black selected-shoe hero
- acid/lime TapSole accent
- large typography
- product-first shoe card
- minimal controls
- bottom mobile navigation
- no confirmation button after registered NFC tap
- no GPS/tracking claims

Once this UI is approved, replace the current V0.x shell page-by-page rather than risking the working core.

V1.0.1: Replaced the temporary ♧ symbol with a simple sneaker outline SVG in PHP/UI. No image file or icon library required.

V1.0.2
- Flatter side-profile shoe outline; no tilt.
- Latest NFC tap time automatically renders in the browser/device timezone.
- Removed hard-coded UTC label.
- No SQL changes.

V1.0.3
- Replaced generic shoe outline with a simple running sneaker outline.
- Horizontal outsole / 0-degree alignment.
- Keeps automatic browser/device timezone from V1.0.2.
