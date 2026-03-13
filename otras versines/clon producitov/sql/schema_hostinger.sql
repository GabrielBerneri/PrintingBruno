-- ============================================================
-- PrintingBruno - Schema para importar en Hostinger/phpMyAdmin
-- ============================================================
-- ⚠️  NO incluye CREATE DATABASE ni USE
--     Importar desde phpMyAdmin con el DB ya seleccionado
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Products table
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  price DECIMAL(10, 2) NOT NULL,
  category ENUM('figuras', 'decoracion', 'funcional', 'personalizado', 'mates', 'filamentos') NOT NULL DEFAULT 'funcional',
  image_url VARCHAR(500) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(30) DEFAULT NULL,
  customer_name VARCHAR(255) NOT NULL,
  customer_email VARCHAR(255) NOT NULL,
  customer_phone VARCHAR(50) DEFAULT NULL,
  total DECIMAL(10, 2) NOT NULL,
  status ENUM('pending', 'approved', 'rejected', 'refunded', 'in_process', 'cancelled', 'shipped', 'delivered') NOT NULL DEFAULT 'pending',
  mp_payment_id VARCHAR(100) DEFAULT NULL,
  mp_preference_id VARCHAR(100) DEFAULT NULL,
  mp_merchant_order_id VARCHAR(100) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_mp_payment (mp_payment_id),
  INDEX idx_mp_preference (mp_preference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email logs table for auditing sent emails
CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body_html MEDIUMTEXT NOT NULL,
    status ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ⚠️  Admin por defecto: usuario "admin", password "admin123"
-- OBLIGATORIO cambiar la contraseña antes de publicar (ver instrucciones deploy)
INSERT INTO admin_users (username, password_hash) VALUES
  ('admin', '$2y$10$8K1p/aGZ1xLvDqOKxQXKxu8rYcGkXhLpQHrg2PfLR5xkxQMZkmiPm');

-- Catálogo de productos reales
INSERT INTO products (name, slug, description, price, category, image_url, badge, material, stock, active, featured) VALUES
  ('Mate River Plate', 'mate-river-plate', 'Mate con escudo de River Plate impreso en 3D. Incluye virola de aluminio. Ideal para el hincha millonario.', 8500.00, 'mates', 'assets/productos/mate-river-plate.jpeg', 'Popular', 'PLA', 10, 1, 1),
  ('Mate Boca Juniors', 'mate-boca-juniors', 'Mate con escudo de Boca Juniors impreso en 3D. Incluye virola de aluminio. Para el hincha xeneize.', 8500.00, 'mates', 'assets/productos/mate-boca-juniors.jpeg', 'Popular', 'PLA', 10, 1, 1),
  ('Mate Independiente', 'mate-independiente', 'Jarra/mate con escudo del Club Atlético Independiente. Impresión 3D con virola metálica y asa.', 9500.00, 'mates', 'assets/productos/mate-independiente.jpeg', NULL, 'PLA', 8, 1, 1),
  ('Llavero Guantes de Boxeo', 'llavero-guantes-boxeo', 'Llavero de guantes de boxeo con detalle realista. Personalizable con inicial grabada.', 3500.00, 'personalizado', 'assets/productos/llavero-guantes-boxeo.jpeg', 'Nuevo', 'PLA', 25, 1, 1),
  ('Filamento PLA Negro - Black Edition', 'filamento-pla-negro', 'Filamento PLA 1.75mm negro premium. 1kg por rollo. Excelente calidad de impresión.', 12000.00, 'filamentos', 'assets/productos/filamento-pla-negro.jpeg', NULL, 'PLA', 15, 1, 0),
  ('Filamento PLA Blanco - Black Edition', 'filamento-pla-blanco', 'Filamento PLA 1.75mm blanco premium. 1kg por rollo. Acabado limpio y consistente.', 12000.00, 'filamentos', 'assets/productos/filamento-pla-blanco.jpeg', NULL, 'PLA', 15, 1, 0),
  ('Filamento PLA Blanco - High Speed', 'filamento-pla-blanco-hs', 'Filamento PLA High Speed 1.75mm blanco. Ideal para impresión rápida sin perder calidad.', 14000.00, 'filamentos', 'assets/productos/filamento-pla-blanco-hs.jpeg', 'Nuevo', 'PLA', 10, 1, 0),
  ('Filamento PLA Negro - High Speed', 'filamento-pla-negro-hs', 'Filamento PLA High Speed 1.75mm negro. Velocidad y precisión en cada capa.', 14000.00, 'filamentos', 'assets/productos/filamento-pla-negro-hs.jpeg', 'Nuevo', 'PLA', 10, 1, 0);

SET FOREIGN_KEY_CHECKS = 1;
