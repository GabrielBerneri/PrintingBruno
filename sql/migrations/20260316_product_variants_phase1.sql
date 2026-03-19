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

ALTER TABLE order_items
  ADD COLUMN IF NOT EXISTS variant_id INT NULL AFTER product_id,
  ADD COLUMN IF NOT EXISTS variant_label VARCHAR(120) DEFAULT NULL AFTER variant_id,
  ADD COLUMN IF NOT EXISTS variant_primary_color VARCHAR(50) DEFAULT NULL AFTER variant_label,
  ADD COLUMN IF NOT EXISTS variant_secondary_color VARCHAR(50) DEFAULT NULL AFTER variant_primary_color,
  ADD INDEX idx_order_item_variant (variant_id),
  ADD CONSTRAINT fk_order_item_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL;

ALTER TABLE stock_reservations
  DROP INDEX uniq_order_product_reservation,
  ADD COLUMN IF NOT EXISTS variant_id INT NULL AFTER product_id,
  ADD UNIQUE KEY uniq_order_product_variant_reservation (order_id, product_id, variant_id),
  ADD INDEX idx_reservation_variant_status (variant_id, status),
  ADD CONSTRAINT fk_stock_reservation_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL;

INSERT INTO product_variants (
  product_id, label, primary_color, secondary_color, sku, price, stock, image_url, image_urls, active, sort_order
)
SELECT
  p.id,
  'Base',
  NULL,
  NULL,
  NULL,
  NULL,
  p.stock,
  p.image_url,
  p.image_urls,
  1,
  0
FROM products p
LEFT JOIN product_variants pv ON pv.product_id = p.id
WHERE pv.id IS NULL;
