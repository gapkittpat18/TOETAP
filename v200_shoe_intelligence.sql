-- TOETAP V2.0 — Shoe Intelligence
USE tapsole_v0;

ALTER TABLE user_shoes
  ADD COLUMN IF NOT EXISTS purchase_price DECIMAL(10,2) NULL AFTER color,
  ADD COLUMN IF NOT EXISTS purchase_currency VARCHAR(3) NOT NULL DEFAULT 'THB' AFTER purchase_price;

-- No destructive activity migration is required.
-- Existing activity_metrics is used for optional HR data on manual/local activities.
