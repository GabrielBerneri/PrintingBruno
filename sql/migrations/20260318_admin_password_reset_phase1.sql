ALTER TABLE admin_users
  ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL AFTER username,
  ADD COLUMN IF NOT EXISTS password_reset_token_hash CHAR(64) NULL AFTER password_hash,
  ADD COLUMN IF NOT EXISTS password_reset_expires_at DATETIME NULL AFTER password_reset_token_hash,
  ADD COLUMN IF NOT EXISTS password_reset_requested_at DATETIME NULL AFTER password_reset_expires_at;

ALTER TABLE admin_users
  ADD UNIQUE KEY IF NOT EXISTS uniq_admin_email (email);
