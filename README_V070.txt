TapSole V0.7 — Shoe Onboarding
================================
INSTALL
1) Run v070_shoe_onboarding.sql ONCE in tapsole_v0.
2) Copy/overwrite these files into C:\xampp\htdocs\tapsole\
   assets/app.css
   app_nav.php
   shoe_search.php
   shoe_add.php
   shoe_ready.php
   shoes.php
   shoe.php
3) Keep your current V0.6.1 index.php.
4) Keep V0.5 tap.php + webhook processor unchanged.
5) Keep config/database.php and config/strava.php unchanged.

NEW FLOW
Register NFC -> physical first tap -> Search/choose shoe -> optionally link existing Strava shoe
-> current Strava mileage imported -> Activate -> You're Ready -> future registered taps are immediate.

The shoe library is intentionally a small V0.7 seed catalog. Unknown models can still be entered manually.
Production can later replace this with a much larger master catalog / AI identification.
