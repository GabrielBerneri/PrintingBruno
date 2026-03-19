-- Migration: product_colors_phase1
-- Adds product_colors catalog table and optional color ID columns on product_variants.
-- Idempotent: safe to run multiple times.

CREATE TABLE IF NOT EXISTS product_colors (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    slug          VARCHAR(120) NOT NULL,
    hex_primary   CHAR(7)      NOT NULL,
    hex_secondary CHAR(7)      DEFAULT NULL,
    active        TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order    INT          NOT NULL DEFAULT 0,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_color_slug (slug),
    INDEX idx_color_active (active),
    INDEX idx_color_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add optional FK columns to product_variants (backward-compatible).
-- Existing rows keep primary_color / secondary_color text fields untouched.
ALTER TABLE product_variants
    ADD COLUMN IF NOT EXISTS primary_color_id   INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS secondary_color_id INT DEFAULT NULL;

-- Indexes (IF NOT EXISTS not supported for indexes in older MySQL; use CREATE INDEX pattern)
-- Use same names as schema.sql to avoid redundant duplicates when combined migration also runs.
SET @idx1 = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'product_variants'
      AND INDEX_NAME = 'idx_variant_primary_color'
);
SET @sql1 = IF(@idx1 = 0,
    'ALTER TABLE product_variants ADD INDEX idx_variant_primary_color (primary_color_id)',
    'SELECT 1'
);
PREPARE stmt1 FROM @sql1; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

SET @idx2 = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'product_variants'
      AND INDEX_NAME = 'idx_variant_secondary_color'
);
SET @sql2 = IF(@idx2 = 0,
    'ALTER TABLE product_variants ADD INDEX idx_variant_secondary_color (secondary_color_id)',
    'SELECT 1'
);
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;
