USE tapsole_v0;
ALTER TABLE tags ADD COLUMN IF NOT EXISTS owner_user_id INT UNSIGNED NULL AFTER tag_code;
ALTER TABLE tags ADD COLUMN IF NOT EXISTS public_token VARCHAR(64) NULL AFTER owner_user_id;
UPDATE tags t JOIN user_shoes us ON us.id=t.user_shoe_id SET t.owner_user_id=us.user_id WHERE t.owner_user_id IS NULL;
UPDATE tags SET public_token=LOWER(CONCAT(REPLACE(UUID(),'-',''),REPLACE(UUID(),'-',''))) WHERE public_token IS NULL OR public_token='';
CREATE UNIQUE INDEX IF NOT EXISTS UX_tags_public_token ON tags(public_token);
CREATE INDEX IF NOT EXISTS IX_tags_owner ON tags(owner_user_id);
