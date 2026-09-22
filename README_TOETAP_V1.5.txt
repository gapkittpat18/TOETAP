TOETAP V1.5 — ACCOUNT / MULTI-USER FOUNDATION
==============================================

What changed
- Email/password account registration.
- Login/logout with PHP sessions.
- Passwords use PHP password_hash/password_verify.
- Session ID is regenerated on login.
- User-facing pages now use the logged-in user instead of SELECT first user.
- Shoes, tags, selections, manual runs, Home and Insights are scoped by session user.
- Existing Strava-first behavior is preserved.
- Public NFC tap.php and webhook/core are intentionally not forced behind login.

Install
1. Overlay V1.5 on V1.4.
2. Run v150_multiuser.sql ONCE.
3. For the existing prototype account, open setup_existing_account.php once and set a password.
4. After success, DELETE setup_existing_account.php.
5. Sign in via login.php.
6. New beta users can use register.php.

Important beta work still needed
- Audit Strava OAuth callback/webhook ownership so athlete_id always maps to the correct TOETAP user.
- Production email verification/password reset.
- CSRF tokens for state-changing forms.
- Replace sequential public NFC identifiers with opaque public tokens before external beta.
- Stable HTTPS domain before writing/locking production NFC tags.

No destructive DB rename. Existing tapsole_v0/path/tag IDs remain compatible.
