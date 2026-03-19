ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS idempotency_key VARCHAR(100) DEFAULT NULL AFTER order_number,
  ADD COLUMN IF NOT EXISTS checkout_status ENUM('pending', 'ready', 'completed', 'failed') NOT NULL DEFAULT 'pending' AFTER status,
  ADD COLUMN IF NOT EXISTS email_status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending' AFTER checkout_status,
  ADD COLUMN IF NOT EXISTS email_error TEXT DEFAULT NULL AFTER email_status,
  ADD COLUMN IF NOT EXISTS mp_preference_status ENUM('not_required', 'pending', 'created', 'failed') NOT NULL DEFAULT 'pending' AFTER payment_method,
  ADD COLUMN IF NOT EXISTS mp_init_point TEXT DEFAULT NULL AFTER mp_preference_id;

ALTER TABLE orders
  ADD UNIQUE KEY uniq_idempotency_key (idempotency_key);

CREATE TABLE IF NOT EXISTS stock_reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  status ENUM('active', 'consumed', 'released', 'expired', 'restored') NOT NULL DEFAULT 'active',
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_order_product_reservation (order_id, product_id),
  INDEX idx_reservation_status_expiry (status, expires_at),
  INDEX idx_reservation_product_status (product_id, status),
  CONSTRAINT fk_stock_reservation_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_stock_reservation_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

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
);
