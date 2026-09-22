-- TOETAP V2.2.26
ALTER TABLE user_shoes
  ADD COLUMN original_photo_path VARCHAR(255) NULL AFTER custom_photo_path;
