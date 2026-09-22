-- TOETAP V1.5 multi-user/auth foundation
ALTER TABLE users ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NULL AFTER email;
ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP;

-- Emails must identify accounts. Clean duplicates manually before enabling if your DB already has duplicates.
CREATE UNIQUE INDEX IF NOT EXISTS UX_users_email ON users(email);

-- Optional: set a password for the existing prototype account using the one-time page setup_existing_account.php.
