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
