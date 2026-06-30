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
  transfer_discount TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_category (category),
  INDEX idx_active (active),
  INDEX idx_featured (featured)
) ENGINE=InnoDB;

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(30) DEFAULT NULL,
  idempotency_key VARCHAR(100) DEFAULT NULL,
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
  INDEX idx_mp_payment (mp_payment_id),
  INDEX idx_mp_preference (mp_preference_id),
  INDEX idx_payment_verified_by (payment_verified_by)
) ENGINE=InnoDB;

-- Product variants table
CREATE TABLE IF NOT EXISTS product_variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  label VARCHAR(120) NOT NULL DEFAULT 'Base',
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
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
