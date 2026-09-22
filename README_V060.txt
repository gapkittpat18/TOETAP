TapSole V0.6 — V1-style Web App UI + NFC Registration
==========================================================

Built as an upgrade on top of the user's working V0.5.

INSTALL
1. Extract this ZIP.
2. Copy these files/folders into C:\xampp\htdocs\tapsole\ and overwrite when asked:
   - index.php
   - shoes.php
   - tags.php
   - tag_new.php
   - shoe_add.php
   - app_nav.php
   - assets\app.css
3. No SQL migration is required.
4. Keep your existing V0.5 tap.php and webhook/processor.php.
5. Keep your existing config/strava.php and database.php.

REGISTER A SECOND NFC
1. Open TapSole Home.
2. Tap REGISTER NEW NFC.
3. Choose:
   - New shoe: first physical tap opens Add Shoe.
   - Existing shoe: makes a spare NFC for that shoe.
4. Tap CREATE TAPSOLE TAG.
5. TapSole automatically creates TS000002 / TS000003 / ...
6. Copy the URL shown.
7. In NFC writer choose URL / URI record and write the URL.
8. Do NOT lock/read-only during prototype.
9. Test the physical NFC.

IMPORTANT
- The URL is generated from the host currently serving the page. With Cloudflare Quick Tunnel,
  open the web app through the current trycloudflare.com address before registering the tag.
- Quick Tunnel URLs change when the tunnel changes, so prototype NFC tags may need rewriting.
- Production should use a permanent domain and pre-issued TapSole tag IDs.
- V0.5 Latest Tap Wins + 6-hour matching remains unchanged.
