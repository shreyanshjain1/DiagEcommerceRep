-- Patch: supplier/vendor metadata and commercial product fields
CREATE TABLE IF NOT EXISTS suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  contact_person VARCHAR(120) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  website VARCHAR(190) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE products ADD COLUMN supplier_id INT DEFAULT NULL AFTER category_id;
ALTER TABLE products ADD COLUMN vendor_sku VARCHAR(80) DEFAULT NULL AFTER sku;
ALTER TABLE products ADD COLUMN availability_status ENUM('in_stock','low_stock','out_of_stock','backorder','preorder','discontinued') NOT NULL DEFAULT 'in_stock' AFTER brand;
ALTER TABLE products ADD COLUMN unit_of_measure VARCHAR(40) DEFAULT NULL AFTER availability_status;
ALTER TABLE products ADD COLUMN pack_size VARCHAR(80) DEFAULT NULL AFTER unit_of_measure;
ALTER TABLE products ADD COLUMN moq INT NOT NULL DEFAULT 1 AFTER pack_size;
ALTER TABLE products ADD COLUMN lead_time_days INT DEFAULT NULL AFTER moq;
ALTER TABLE products ADD COLUMN lead_time_note VARCHAR(190) DEFAULT NULL AFTER lead_time_days;
ALTER TABLE products ADD CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL;
CREATE INDEX idx_products_supplier ON products(supplier_id);
CREATE INDEX idx_products_availability ON products(availability_status);
