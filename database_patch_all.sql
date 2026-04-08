-- =========================================================
-- Pharmastar Diagnostics RFQ Platform
-- Consolidated database upgrade bundle
--
-- Purpose:
--   Apply the major schema upgrades from the incremental
--   database_patch_*.sql files in a single migration script.
--
-- Notes:
--   1) This bundle is intended for EXISTING databases that were
--      originally created from an older version of database.sql.
--   2) On a fresh setup, importing the latest database.sql is still
--      the cleanest path.
--   3) This file consolidates the patch history for cleaner repo
--      presentation and simpler import instructions.
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- ---------------------------------------------------------
-- 1) Password reset support
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE UNIQUE INDEX uidx_password_resets_token_hash ON password_resets(token_hash);
CREATE INDEX idx_password_resets_user ON password_resets(user_id);
CREATE INDEX idx_password_resets_expires_at ON password_resets(expires_at);

-- ---------------------------------------------------------
-- 2) Formal quotation fields
-- ---------------------------------------------------------
ALTER TABLE quotes
  ADD COLUMN overhead_charge DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER shipping_fee,
  ADD COLUMN other_expenses DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER overhead_charge,
  ADD COLUMN installation_expenses DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER other_expenses,
  ADD COLUMN valid_until DATE DEFAULT NULL AFTER installation_expenses,
  ADD COLUMN lead_time VARCHAR(120) DEFAULT NULL AFTER valid_until,
  ADD COLUMN warranty VARCHAR(120) DEFAULT NULL AFTER lead_time,
  ADD COLUMN payment_terms TEXT DEFAULT NULL AFTER warranty,
  ADD COLUMN sent_at DATETIME DEFAULT NULL AFTER payment_terms,
  ADD COLUMN sent_to VARCHAR(190) DEFAULT NULL AFTER sent_at,
  ADD COLUMN sent_by INT DEFAULT NULL AFTER sent_to;

ALTER TABLE quotes
  ADD CONSTRAINT fk_quotes_sent_by FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE SET NULL;

-- ---------------------------------------------------------
-- 3) Audit logs
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id INT NOT NULL,
  action VARCHAR(80) NOT NULL,
  before_json LONGTEXT DEFAULT NULL,
  after_json LONGTEXT DEFAULT NULL,
  meta_json LONGTEXT DEFAULT NULL,
  ip_address VARCHAR(64) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at);
CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);

-- ---------------------------------------------------------
-- 4) Admin user management fields
-- ---------------------------------------------------------
ALTER TABLE users
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role,
  ADD COLUMN last_login_at DATETIME DEFAULT NULL AFTER is_active,
  ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- ---------------------------------------------------------
-- 5) RFQ status history timeline
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS quote_status_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quote_id INT NOT NULL,
  event_type VARCHAR(50) NOT NULL,
  from_status VARCHAR(30) DEFAULT NULL,
  to_status VARCHAR(30) DEFAULT NULL,
  note TEXT DEFAULT NULL,
  meta_json LONGTEXT DEFAULT NULL,
  acted_by INT DEFAULT NULL,
  ip_address VARCHAR(64) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_quote_status_history_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
  CONSTRAINT fk_quote_status_history_user FOREIGN KEY (acted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_quote_status_history_quote_created ON quote_status_history(quote_id, created_at);
CREATE INDEX idx_quote_status_history_event ON quote_status_history(event_type);

-- ---------------------------------------------------------
-- 6) Quote revision history
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS quote_revisions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quote_id INT NOT NULL,
  version_no INT NOT NULL,
  reason VARCHAR(255) NULL,
  status_snapshot VARCHAR(30) NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  overhead_charge DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  other_expenses DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  installation_expenses DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  valid_until DATE NULL,
  lead_time VARCHAR(150) NULL,
  warranty VARCHAR(150) NULL,
  payment_terms TEXT NULL,
  admin_notes TEXT NULL,
  meta_json JSON NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_quote_revision_version (quote_id, version_no),
  KEY idx_quote_revisions_quote_created (quote_id, created_at),
  CONSTRAINT fk_quote_revisions_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
  CONSTRAINT fk_quote_revisions_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quote_revision_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  revision_id INT NOT NULL,
  quote_item_id INT NULL,
  product_id INT NULL,
  product_name VARCHAR(255) NOT NULL,
  sku VARCHAR(120) NULL,
  brand VARCHAR(150) NULL,
  qty INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  line_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_quote_revision_items_revision (revision_id),
  KEY idx_quote_revision_items_product (product_id),
  CONSTRAINT fk_quote_revision_items_revision FOREIGN KEY (revision_id) REFERENCES quote_revisions(id) ON DELETE CASCADE,
  CONSTRAINT fk_quote_revision_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- 7) Quote approval / rejection
-- ---------------------------------------------------------
ALTER TABLE quotes
  ADD COLUMN approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER status,
  ADD COLUMN approval_note TEXT DEFAULT NULL AFTER approval_status,
  ADD COLUMN approval_decided_at DATETIME DEFAULT NULL AFTER approval_note;

CREATE INDEX idx_quotes_approval_status ON quotes(approval_status);
CREATE INDEX idx_quotes_user_updated ON quotes(user_id, updated_at);

-- ---------------------------------------------------------
-- 8) Stored quotation documents
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS quote_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quote_id INT NOT NULL,
  document_type ENUM('quotation_html','quotation_pdf','attachment') NOT NULL DEFAULT 'quotation_html',
  title VARCHAR(190) NOT NULL,
  storage_mode ENUM('generated','uploaded') NOT NULL DEFAULT 'generated',
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) DEFAULT NULL,
  file_size INT NOT NULL DEFAULT 0,
  created_by INT DEFAULT NULL,
  is_customer_visible TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_quote_documents_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
  CONSTRAINT fk_quote_documents_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_quote_documents_quote ON quote_documents(quote_id, created_at);

-- ---------------------------------------------------------
-- 9) Supplier / commercial product fields
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- 10) Company accounts and contacts
-- ---------------------------------------------------------
ALTER TABLE users
  ADD COLUMN company_account_id INT DEFAULT NULL AFTER role,
  ADD COLUMN company_contact_role ENUM('primary','billing','procurement','viewer') NOT NULL DEFAULT 'primary' AFTER company_account_id;

CREATE TABLE IF NOT EXISTS company_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_name VARCHAR(190) NOT NULL,
  account_code VARCHAR(40) NOT NULL UNIQUE,
  vat_tin VARCHAR(80) DEFAULT NULL,
  primary_email VARCHAR(190) DEFAULT NULL,
  primary_phone VARCHAR(40) DEFAULT NULL,
  address_line1 VARCHAR(190) DEFAULT NULL,
  address_line2 VARCHAR(190) DEFAULT NULL,
  city VARCHAR(120) DEFAULT NULL,
  province VARCHAR(120) DEFAULT NULL,
  postal_code VARCHAR(20) DEFAULT NULL,
  website VARCHAR(190) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  account_status ENUM('lead','pending_verification','active','inactive') NOT NULL DEFAULT 'pending_verification',
  created_by INT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS company_account_contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_account_id INT NOT NULL,
  user_id INT NOT NULL,
  contact_role ENUM('primary','billing','procurement','viewer') NOT NULL DEFAULT 'viewer',
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  invite_status ENUM('active','invited','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_company_contacts_account FOREIGN KEY (company_account_id) REFERENCES company_accounts(id) ON DELETE CASCADE,
  CONSTRAINT fk_company_contacts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_company_accounts_name ON company_accounts(company_name);
CREATE INDEX idx_company_accounts_status ON company_accounts(account_status);
CREATE INDEX idx_company_contacts_account ON company_account_contacts(company_account_id);
CREATE UNIQUE INDEX uidx_company_contacts_user ON company_account_contacts(user_id);

ALTER TABLE users
  ADD CONSTRAINT fk_users_company_account FOREIGN KEY (company_account_id) REFERENCES company_accounts(id) ON DELETE SET NULL;

ALTER TABLE company_accounts
  ADD CONSTRAINT fk_company_accounts_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- ---------------------------------------------------------
-- 11) Company saved addresses
-- ---------------------------------------------------------
ALTER TABLE company_accounts
  ADD COLUMN address_line1 VARCHAR(190) DEFAULT NULL,
  ADD COLUMN address_line2 VARCHAR(190) DEFAULT NULL,
  ADD COLUMN city VARCHAR(120) DEFAULT NULL,
  ADD COLUMN province VARCHAR(120) DEFAULT NULL,
  ADD COLUMN postal_code VARCHAR(20) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS company_account_addresses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_account_id INT NOT NULL,
  label VARCHAR(120) NOT NULL,
  address_type ENUM('billing','shipping','site','warehouse','other') NOT NULL DEFAULT 'shipping',
  recipient_name VARCHAR(120) DEFAULT NULL,
  recipient_phone VARCHAR(40) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  address_line1 VARCHAR(190) NOT NULL,
  address_line2 VARCHAR(190) DEFAULT NULL,
  city VARCHAR(120) NOT NULL,
  province VARCHAR(120) NOT NULL,
  postal_code VARCHAR(20) DEFAULT NULL,
  country VARCHAR(120) NOT NULL DEFAULT 'Philippines',
  tax_label VARCHAR(120) DEFAULT NULL,
  delivery_notes TEXT DEFAULT NULL,
  is_default_billing TINYINT(1) NOT NULL DEFAULT 0,
  is_default_shipping TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_company_addresses_account FOREIGN KEY (company_account_id) REFERENCES company_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_company_addresses_account ON company_account_addresses(company_account_id);
CREATE INDEX idx_company_addresses_type ON company_account_addresses(address_type);
CREATE INDEX idx_company_addresses_active ON company_account_addresses(is_active);

-- ---------------------------------------------------------
-- 12) Product search FULLTEXT support
-- ---------------------------------------------------------
ALTER TABLE products
  ADD FULLTEXT INDEX ft_products_search (name, sku, brand, short_desc, long_desc);

SET FOREIGN_KEY_CHECKS=1;
