-- Add company-level saved addresses and billing/shipping profiles
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
