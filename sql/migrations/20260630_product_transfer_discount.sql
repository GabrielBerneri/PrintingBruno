ALTER TABLE products
  ADD COLUMN IF NOT EXISTS transfer_discount TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER featured;

ALTER TABLE products
  MODIFY COLUMN transfer_discount TINYINT UNSIGNED NOT NULL DEFAULT 0;

-- Migra el valor booleano anterior (1 = 10% activo) al nuevo modelo de porcentaje
UPDATE products SET transfer_discount = 10 WHERE transfer_discount = 1;
