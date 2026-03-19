-- ============================================
-- PrintingBruno - MySQL Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS printingbruno
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE printingbruno;

-- Products table
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  price DECIMAL(10, 2) NOT NULL,
  category ENUM('figuras', 'decoracion', 'funcional', 'personalizado', 'mates', 'filamentos', 'jarras', 'insumos', 'impresoras', 'llaveros') NOT NULL DEFAULT 'funcional',
  image_url VARCHAR(500) DEFAULT NULL,
  image_urls TEXT DEFAULT NULL,
  badge VARCHAR(50) DEFAULT NULL,
  material VARCHAR(100) DEFAULT 'PLA',
  stock INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_category (category),
  INDEX idx_active (active),
  INDEX idx_featured (featured)
) ENGINE=InnoDB;

-- Product colors catalog
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
) ENGINE=InnoDB;

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(30) DEFAULT NULL,
  idempotency_key VARCHAR(100) DEFAULT NULL,
  customer_id INT DEFAULT NULL,
  customer_name VARCHAR(255) NOT NULL,
  customer_email VARCHAR(255) NOT NULL,
  customer_phone VARCHAR(50) DEFAULT NULL,
  total DECIMAL(10, 2) NOT NULL,
  status ENUM('pending', 'approved', 'rejected', 'refunded', 'in_process', 'cancelled', 'charged_back', 'shipped', 'delivered') NOT NULL DEFAULT 'pending',
  payment_status ENUM('pending', 'under_review', 'approved', 'rejected', 'cancelled', 'refunded', 'charged_back') NOT NULL DEFAULT 'pending',
  fulfillment_status ENUM('queued', 'in_production', 'ready', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'queued',
  checkout_status ENUM('pending', 'ready', 'completed', 'failed') NOT NULL DEFAULT 'pending',
  email_status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
  email_error TEXT DEFAULT NULL,
  payment_method ENUM('mercadopago', 'transferencia', 'efectivo') NOT NULL DEFAULT 'mercadopago',
  payment_notes TEXT DEFAULT NULL,
  payment_reference VARCHAR(120) DEFAULT NULL,
  payment_verified_at DATETIME DEFAULT NULL,
  payment_verified_by INT DEFAULT NULL,
  mp_preference_status ENUM('not_required', 'pending', 'created', 'failed') NOT NULL DEFAULT 'pending',
  mp_payment_id VARCHAR(100) DEFAULT NULL,
  mp_preference_id VARCHAR(100) DEFAULT NULL,
  mp_init_point TEXT DEFAULT NULL,
  mp_merchant_order_id VARCHAR(100) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_idempotency_key (idempotency_key),
  INDEX idx_status (status),
  INDEX idx_payment_status (payment_status),
  INDEX idx_fulfillment_status (fulfillment_status),
  INDEX idx_customer_id (customer_id),
  INDEX idx_mp_payment (mp_payment_id),
  INDEX idx_mp_preference (mp_preference_id),
  INDEX idx_payment_verified_by (payment_verified_by)
) ENGINE=InnoDB;

-- Product variants table
CREATE TABLE IF NOT EXISTS product_variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  label VARCHAR(120) NOT NULL DEFAULT 'Base',
  primary_color_id INT DEFAULT NULL,
  secondary_color_id INT DEFAULT NULL,
  primary_color VARCHAR(50) DEFAULT NULL,
  secondary_color VARCHAR(50) DEFAULT NULL,
  sku VARCHAR(100) DEFAULT NULL,
  price DECIMAL(10, 2) DEFAULT NULL,
  stock INT NOT NULL DEFAULT 0,
  image_url VARCHAR(500) DEFAULT NULL,
  image_urls TEXT DEFAULT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_variant_product (product_id),
  INDEX idx_variant_active (active),
  INDEX idx_variant_sort (product_id, sort_order),
  INDEX idx_variant_primary_color (primary_color_id),
  INDEX idx_variant_secondary_color (secondary_color_id),
  CONSTRAINT fk_product_variant_primary_color FOREIGN KEY (primary_color_id) REFERENCES product_colors(id) ON DELETE SET NULL,
  CONSTRAINT fk_product_variant_secondary_color FOREIGN KEY (secondary_color_id) REFERENCES product_colors(id) ON DELETE SET NULL,
  CONSTRAINT fk_product_variant_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  variant_id INT DEFAULT NULL,
  variant_label VARCHAR(120) DEFAULT NULL,
  variant_primary_color VARCHAR(50) DEFAULT NULL,
  variant_secondary_color VARCHAR(50) DEFAULT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
  INDEX idx_order_item_variant (variant_id),
  INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- Stock reservations table
CREATE TABLE IF NOT EXISTS stock_reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  variant_id INT DEFAULT NULL,
  quantity INT NOT NULL DEFAULT 1,
  status ENUM('active', 'consumed', 'released', 'expired', 'restored') NOT NULL DEFAULT 'active',
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_order_product_variant_reservation (order_id, product_id, variant_id),
  INDEX idx_reservation_status_expiry (status, expires_at),
  INDEX idx_reservation_product_status (product_id, status),
  INDEX idx_reservation_variant_status (variant_id, status),
  CONSTRAINT fk_stock_reservation_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_stock_reservation_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  CONSTRAINT fk_stock_reservation_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Customer accounts
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
) ENGINE=InnoDB;

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
  INDEX idx_customer_addresses_default (customer_id, is_default),
  CONSTRAINT fk_customer_addresses_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

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
  INDEX idx_customer_sessions_expiry (expires_at),
  CONSTRAINT fk_customer_sessions_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

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
  INDEX idx_customer_password_resets_expiry (expires_at),
  CONSTRAINT fk_customer_password_resets_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customer_email_verifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  verified_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_customer_email_verification_token (token_hash),
  INDEX idx_customer_email_verifications_customer (customer_id),
  INDEX idx_customer_email_verifications_expiry (expires_at),
  CONSTRAINT fk_customer_email_verifications_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Admin audit trail
CREATE TABLE IF NOT EXISTS admin_audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_user_id INT DEFAULT NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(100) NOT NULL,
  entity_id INT DEFAULT NULL,
  details_json JSON DEFAULT NULL,
  ip_address VARCHAR(64) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_admin_audit_entity (entity_type, entity_id),
  INDEX idx_admin_audit_user (admin_user_id),
  CONSTRAINT fk_admin_audit_user FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  password_reset_token_hash CHAR(64) DEFAULT NULL,
  password_reset_expires_at DATETIME DEFAULT NULL,
  password_reset_requested_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_admin_email (email)
) ENGINE=InnoDB;

-- Email logs table
CREATE TABLE IF NOT EXISTS email_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NULL,
  recipient_email VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body_html MEDIUMTEXT NOT NULL,
  status ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
  error_message TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_logs_status (status),
  INDEX idx_email_logs_order (order_id),
  CONSTRAINT fk_email_log_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- No se crea admin por defecto.
-- Crear el primer usuario manualmente con:
--   php scripts/create_admin_user.php <usuario>

-- Seed products (real catalog)
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

INSERT INTO products (name, slug, description, price, category, image_url, badge, material, stock, active, featured) VALUES
  ('Mate River Plate', 'mate-river-plate', 'Mate con escudo de River Plate impreso en 3D. Incluye virola de aluminio. Ideal para el hincha millonario.', 8500.00, 'mates', 'assets/productos/mate-river-plate.jpeg', 'Popular', 'PLA', 10, 1, 1),
  ('Mate Boca Juniors', 'mate-boca-juniors', 'Mate con escudo de Boca Juniors impreso en 3D. Incluye virola de aluminio. Para el hincha xeneize.', 8500.00, 'mates', 'assets/productos/mate-boca-juniors.jpeg', 'Popular', 'PLA', 10, 1, 1),
  ('Mate Independiente', 'mate-independiente', 'Jarra/mate con escudo del Club Atlético Independiente. Impresión 3D con virola metálica y asa.', 9500.00, 'mates', 'assets/productos/mate-independiente.jpeg', NULL, 'PLA', 8, 1, 1),
  ('Llavero Guantes de Boxeo', 'llavero-guantes-boxeo', 'Llavero de guantes de boxeo con detalle realista. Personalizable con inicial grabada.', 3500.00, 'personalizado', 'assets/productos/llavero-guantes-boxeo.jpeg', 'Nuevo', 'PLA', 25, 1, 1),
  ('Filamento PLA Negro - Black Edition', 'filamento-pla-negro', 'Filamento PLA 1.75mm negro premium. 1kg por rollo. Excelente calidad de impresión.', 12000.00, 'filamentos', 'assets/productos/filamento-pla-negro.jpeg', NULL, 'PLA', 15, 1, 0),
  ('Filamento PLA Blanco - Black Edition', 'filamento-pla-blanco', 'Filamento PLA 1.75mm blanco premium. 1kg por rollo. Acabado limpio y consistente.', 12000.00, 'filamentos', 'assets/productos/filamento-pla-blanco.jpeg', NULL, 'PLA', 15, 1, 0),
  ('Filamento PLA Blanco - High Speed', 'filamento-pla-blanco-hs', 'Filamento PLA High Speed 1.75mm blanco. Ideal para impresión rápida sin perder calidad.', 14000.00, 'filamentos', 'assets/productos/filamento-pla-blanco-hs.jpeg', 'Nuevo', 'PLA', 10, 1, 0),
  ('Filamento PLA Negro - High Speed', 'filamento-pla-negro-hs', 'Filamento PLA High Speed 1.75mm negro. Velocidad y precisión en cada capa.', 14000.00, 'filamentos', 'assets/productos/filamento-pla-negro-hs.jpeg', 'Nuevo', 'PLA', 10, 1, 0);

INSERT INTO product_variants (product_id, label, stock, image_url, image_urls, active, sort_order)
SELECT id, 'Base', stock, image_url, image_urls, 1, 0
FROM products;
