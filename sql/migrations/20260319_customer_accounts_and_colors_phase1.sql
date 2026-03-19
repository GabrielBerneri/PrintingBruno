CREATE TABLE IF NOT EXISTS product_colors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  hex_primary CHAR(7) NOT NULL,
  hex_secondary CHAR(7) DEFAULT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_product_colors_active_sort (active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO product_colors (name, slug, hex_primary, hex_secondary, active, sort_order) VALUES
  ('Rojo', 'rojo', '#d83b3b', NULL, 1, 10),
  ('Blanco', 'blanco', '#f4f4f1', NULL, 1, 20),
  ('Negro', 'negro', '#1a1a1a', NULL, 1, 30),
  ('Gris', 'gris', '#8c8f96', NULL, 1, 40),
  ('Azul', 'azul', '#2f63d8', NULL, 1, 50),
  ('Verde', 'verde', '#2f9d63', NULL, 1, 60),
  ('Dorado', 'dorado', '#c9a227', NULL, 1, 70),
  ('Celeste', 'celeste', '#6bbef0', NULL, 1, 80),
  ('Rosa', 'rosa', '#ef7ca8', NULL, 1, 90)
ON DUPLICATE KEY UPDATE
  hex_primary = VALUES(hex_primary),
  hex_secondary = VALUES(hex_secondary),
  active = VALUES(active),
  sort_order = VALUES(sort_order);

ALTER TABLE product_variants
  ADD COLUMN IF NOT EXISTS primary_color_id INT DEFAULT NULL AFTER label,
  ADD COLUMN IF NOT EXISTS secondary_color_id INT DEFAULT NULL AFTER primary_color_id;

SET @pvc1 = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND INDEX_NAME = 'idx_variant_primary_color');
SET @pvc1sql = IF(@pvc1 = 0, 'ALTER TABLE product_variants ADD INDEX idx_variant_primary_color (primary_color_id)', 'SELECT 1');
PREPARE pvc1stmt FROM @pvc1sql; EXECUTE pvc1stmt; DEALLOCATE PREPARE pvc1stmt;

SET @pvc2 = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND INDEX_NAME = 'idx_variant_secondary_color');
SET @pvc2sql = IF(@pvc2 = 0, 'ALTER TABLE product_variants ADD INDEX idx_variant_secondary_color (secondary_color_id)', 'SELECT 1');
PREPARE pvc2stmt FROM @pvc2sql; EXECUTE pvc2stmt; DEALLOCATE PREPARE pvc2stmt;

UPDATE product_variants pv
LEFT JOIN product_colors pc ON LOWER(pc.slug) = LOWER(TRIM(pv.primary_color))
SET pv.primary_color_id = pc.id
WHERE pv.primary_color IS NOT NULL
  AND TRIM(pv.primary_color) <> ''
  AND pv.primary_color_id IS NULL;

UPDATE product_variants pv
LEFT JOIN product_colors pc ON LOWER(pc.slug) = LOWER(TRIM(pv.secondary_color))
SET pv.secondary_color_id = pc.id
WHERE pv.secondary_color IS NOT NULL
  AND TRIM(pv.secondary_color) <> ''
  AND pv.secondary_color_id IS NULL;

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  email_normalized VARCHAR(255) NOT NULL,
  first_name VARCHAR(120) NOT NULL,
  last_name VARCHAR(120) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  dni VARCHAR(30) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  verified_at DATETIME DEFAULT NULL,
  last_login_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_customers_email_normalized (email_normalized),
  INDEX idx_customers_verified (verified_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_addresses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  label VARCHAR(80) DEFAULT NULL,
  recipient_name VARCHAR(180) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  street VARCHAR(180) NOT NULL,
  city VARCHAR(120) NOT NULL,
  province VARCHAR(120) NOT NULL,
  postal_code VARCHAR(20) NOT NULL,
  notes VARCHAR(255) DEFAULT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customer_addresses_customer (customer_id),
  INDEX idx_customer_addresses_default (customer_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  csrf_token CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  last_seen_at DATETIME DEFAULT NULL,
  ip_address VARCHAR(64) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_customer_sessions_token (token_hash),
  INDEX idx_customer_sessions_customer (customer_id),
  INDEX idx_customer_sessions_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  requested_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_customer_password_reset_token (token_hash),
  INDEX idx_customer_password_resets_customer (customer_id),
  INDEX idx_customer_password_resets_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_email_verifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  verified_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_customer_email_verification_token (token_hash),
  INDEX idx_customer_email_verifications_customer (customer_id),
  INDEX idx_customer_email_verifications_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS customer_id INT DEFAULT NULL AFTER idempotency_key;

SET @oci = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_customer_id');
SET @ocisql = IF(@oci = 0, 'ALTER TABLE orders ADD INDEX idx_customer_id (customer_id)', 'SELECT 1');
PREPARE ocistmt FROM @ocisql; EXECUTE ocistmt; DEALLOCATE PREPARE ocistmt;
