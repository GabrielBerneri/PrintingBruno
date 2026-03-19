ALTER TABLE orders
  MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'refunded', 'in_process', 'cancelled', 'charged_back', 'shipped', 'delivered') NOT NULL DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'under_review', 'approved', 'rejected', 'cancelled', 'refunded', 'charged_back') NOT NULL DEFAULT 'pending' AFTER status,
  ADD COLUMN IF NOT EXISTS fulfillment_status ENUM('queued', 'in_production', 'ready', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'queued' AFTER payment_status,
  ADD COLUMN IF NOT EXISTS payment_notes TEXT DEFAULT NULL AFTER payment_method,
  ADD COLUMN IF NOT EXISTS payment_verified_at DATETIME DEFAULT NULL AFTER payment_notes,
  ADD COLUMN IF NOT EXISTS payment_verified_by INT DEFAULT NULL AFTER payment_verified_at;

UPDATE orders
SET
  payment_status = CASE
    WHEN status = 'approved' THEN 'approved'
    WHEN status = 'pending' THEN 'pending'
    WHEN status = 'rejected' THEN 'rejected'
    WHEN status = 'refunded' THEN 'refunded'
    WHEN status = 'cancelled' THEN 'cancelled'
    WHEN status = 'charged_back' THEN 'charged_back'
    WHEN status = 'shipped' THEN 'approved'
    WHEN status = 'delivered' THEN 'approved'
    WHEN status = 'in_process' AND checkout_status = 'completed' THEN 'approved'
    WHEN status = 'in_process' THEN 'under_review'
    ELSE 'pending'
  END,
  fulfillment_status = CASE
    WHEN status = 'shipped' THEN 'shipped'
    WHEN status = 'delivered' THEN 'delivered'
    WHEN status = 'in_process' AND checkout_status = 'completed' THEN 'in_production'
    WHEN status IN ('rejected', 'cancelled', 'refunded', 'charged_back') THEN 'cancelled'
    ELSE 'queued'
  END;

ALTER TABLE orders
  ADD INDEX idx_payment_status (payment_status),
  ADD INDEX idx_fulfillment_status (fulfillment_status),
  ADD INDEX idx_payment_verified_by (payment_verified_by);
