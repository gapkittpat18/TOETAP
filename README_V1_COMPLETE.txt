TOETAP V1 COMPLETE UI
======================
Overlay this package on the existing working TOETAP folder.

V1 pages included:
- index.php
- shoes.php
- shoe.php
- shoe_add.php
- shoe_search.php
- shoe_ready.php
- tags.php
- tag_new.php
- analytics.php
- settings.php
- strava_v1.php
- v1_nav.php
- assets/v1.css
- assets/sneaker.png

Keeps the existing core files untouched:
- tap.php
- config/
- strava/functions.php
- strava/connect.php
- webhook processor / matching core

Important:
- No SQL changes.
- Uses the supplied sneaker image.
- Home NFC timestamp still auto-renders in browser/device timezone.
- Navigation no longer points to index_v1.php / shoes_v1.php / tags_v1.php.
- Insights is now V1 styled.
- Settings and Strava connection are V1 styled.
- tag_new.php remains a PROTOTYPE tag minting page. Production should use pre-issued opaque/public tokens.
