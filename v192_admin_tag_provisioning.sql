-- TOETAP V1.9.2 — Admin-only NFC tag provisioning
USE tapsole_v0;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER name;

-- IMPORTANT:
-- Promote only your own TOETAP account after replacing the email below.
-- UPDATE users SET is_admin=1 WHERE email='YOUR_EMAIL_HERE';
