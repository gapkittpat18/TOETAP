TOETAP V1.6.2 — DYNAMIC STRAVA CALLBACK HOST
=============================================
Fixes the remaining localhost redirect.

Previously config/strava.php contained a fixed redirect_uri pointing to localhost.
V1.6.2 no longer uses that fixed URI when starting OAuth.

connect.php now builds redirect_uri from the URL actually used to open TOETAP:
- Open TOETAP via localhost -> callback is localhost.
- Open TOETAP via Cloudflare HTTPS URL -> callback uses that Cloudflare HTTPS host.
- Open from phone through public URL -> Strava returns to that same public host.

TEST
1. Keep XAMPP + cloudflared running.
2. On phone, open the CURRENT public Cloudflare URL, not localhost.
3. Login to TOETAP through that public URL.
4. Open /tapsole/strava/callback_info.php.
5. It displays the exact Redirect URI and Callback Domain currently detected.
6. Put the displayed Callback Domain into the Strava API app settings if needed.
7. Tap CONNECT STRAVA from that page.

No SQL changes.
Do not lock NFC while using temporary Quick Tunnel URLs.
