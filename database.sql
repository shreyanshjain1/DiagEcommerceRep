-- Pharmastar Diagnostics – E-commerce Database (RFQ Only)
-- Compatible with MySQL 8 / MariaDB
-- NOTE: On shared hosting, create your database in cPanel first, then import this file into that database.

SET FOREIGN_KEY_CHECKS=0;

-- Pharmastar Diagnostics – E-commerce Database
-- Currency: PHP (₱)
--
-- IMPORTANT:
-- If you already have production data, REMOVE the DROP DATABASE line below.


SET time_zone = '+08:00';

-- =========================
-- USERS
-- =========================
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  company VARCHAR(190) DEFAULT NULL,
  vat_tin VARCHAR(80) DEFAULT NULL,
  default_payment_method VARCHAR(60) DEFAULT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  company_account_id INT DEFAULT NULL,
  company_contact_role ENUM('primary','billing','procurement','viewer') NOT NULL DEFAULT 'primary',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE company_accounts (
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

CREATE TABLE company_account_contacts (
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


CREATE TABLE company_account_addresses (
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

-- =========================
-- SETTINGS (admin-configurable site values)
-- =========================
CREATE TABLE settings (
  `key` VARCHAR(120) PRIMARY KEY,
  `value` TEXT NOT NULL
) ENGINE=InnoDB;

-- =========================
-- CATEGORIES
-- =========================
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT DEFAULT NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_categories_slug ON categories(slug);

-- =========================
-- PRODUCTS
-- =========================
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  sku VARCHAR(80) DEFAULT NULL,
  brand VARCHAR(120) DEFAULT NULL,
  short_desc VARCHAR(255) DEFAULT NULL,
  long_desc TEXT DEFAULT NULL,
  specs_json LONGTEXT DEFAULT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_products_sku ON products(sku);
CREATE INDEX idx_products_brand ON products(brand);
CREATE INDEX idx_products_active ON products(is_active);

CREATE TABLE product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  title VARCHAR(190) NOT NULL,
  label VARCHAR(190) DEFAULT NULL,
  file_path VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_documents_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- INQUIRIES (Quotations / Questions)
-- =========================
CREATE TABLE inquiries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  product_id INT DEFAULT NULL,
  company VARCHAR(190) DEFAULT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  subject VARCHAR(190) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('New','In Progress','Closed') NOT NULL DEFAULT 'New',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_inquiries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_inquiries_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_inquiries_status ON inquiries(status);

-- =========================
-- CARTS
-- =========================
CREATE TABLE carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cart_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  price_at_time DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE UNIQUE INDEX uidx_cart_item ON cart_items(cart_id, product_id);

-- =========================
-- ORDERS
-- =========================
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  order_number VARCHAR(30) NOT NULL UNIQUE,
  status ENUM('Pending','Paid','Processing','Shipped','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  po_number VARCHAR(80) DEFAULT NULL,
  vat_tin VARCHAR(80) DEFAULT NULL,
  payment_method VARCHAR(60) NOT NULL DEFAULT 'Bank Transfer',
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  overhead_charge DECIMAL(12,2) NOT NULL DEFAULT 0,
  other_expenses DECIMAL(12,2) NOT NULL DEFAULT 0,
  installation_expenses DECIMAL(12,2) NOT NULL DEFAULT 0,
  valid_until DATE DEFAULT NULL,
  lead_time VARCHAR(120) DEFAULT NULL,
  warranty VARCHAR(120) DEFAULT NULL,
  payment_terms TEXT DEFAULT NULL,
  sent_at DATETIME DEFAULT NULL,
  sent_to VARCHAR(190) DEFAULT NULL,
  sent_by INT DEFAULT NULL,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  name VARCHAR(120) NOT NULL,
  company VARCHAR(190) DEFAULT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  address_line1 VARCHAR(190) NOT NULL,
  address_line2 VARCHAR(190) DEFAULT NULL,
  city VARCHAR(120) NOT NULL,
  province VARCHAR(120) NOT NULL,
  postal_code VARCHAR(20) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at);

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT DEFAULT NULL,
  qty INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================
-- QUOTES (Saved quotations)
-- =========================
CREATE TABLE quotes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  quote_number VARCHAR(30) NOT NULL UNIQUE,
  status ENUM('draft','submitted','quoted','closed') NOT NULL DEFAULT 'draft',
  notes TEXT DEFAULT NULL,
  admin_notes TEXT DEFAULT NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  overhead_charge DECIMAL(12,2) NOT NULL DEFAULT 0,
  other_expenses DECIMAL(12,2) NOT NULL DEFAULT 0,
  installation_expenses DECIMAL(12,2) NOT NULL DEFAULT 0,
  valid_until DATE DEFAULT NULL,
  lead_time VARCHAR(120) DEFAULT NULL,
  warranty VARCHAR(120) DEFAULT NULL,
  payment_terms TEXT DEFAULT NULL,
  sent_at DATETIME DEFAULT NULL,
  sent_to VARCHAR(190) DEFAULT NULL,
  sent_by INT DEFAULT NULL,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  name VARCHAR(120) NOT NULL,
  company VARCHAR(190) DEFAULT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_quotes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_quotes_sent_by FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE quote_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quote_id INT NOT NULL,
  product_id INT DEFAULT NULL,
  qty INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_quote_items_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
  CONSTRAINT fk_quote_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================
-- SEED DATA
-- =========================

-- Default admin account
-- Email: admin@pharmastar.local
-- Password: Admin@123
INSERT INTO users(name,email,password_hash,phone,company,role) VALUES
('Pharmastar Admin','admin@pharmastar.local', '$2y$10$9SHxAalo57pzlOUq82qz0erWKZoZ7Ualh7e2UY6ODj0mC/pLEAAga', '09691229230', 'Pharmastar Int''l Trading Corp', 'admin');

-- Site settings (editable in Admin → Settings)
INSERT INTO settings(`key`,`value`) VALUES
('contact_email','shreyanshjain@pharmastar.com.ph'),
('contact_hotline','+639691229230'),
('contact_whatsapp','09453462354');

-- Categories (Instruments + Reagents + Consumables)
INSERT INTO categories(name,slug,sort_order) VALUES
('Electrolyte Analyzers','electrolyte-analyzers',10),
('Clinical Chemistry Analyzers','clinical-chemistry-analyzers',20),
('Hematology Analyzers','hematology-analyzers',30),
('Immunoassay (CLIA) Analyzers','immunoassay-clia-analyzers',40),
('Urinalysis Analyzers','urinalysis-analyzers',50),
('HbA1c / HPLC Analyzers','hba1c-hplc-analyzers',60),
('Reagents & Kits','reagents-kits',70),
('Consumables & Accessories','consumables-accessories',80);

-- Products (RFQ-based catalog)
-- NOTE: Prices are placeholders in PHP; update based on your latest price list.

-- Electrolyte
INSERT INTO products(category_id,name,slug,sku,brand,short_desc,long_desc,specs_json,price,stock,is_active)
VALUES
((SELECT id FROM categories WHERE slug='electrolyte-analyzers'), 'Mispa Lyte Electrolyte Analyzer', 'mispa-lyte', 'MISPA-LYTE', 'Agappe', 'Compact ISE electrolyte analyzer for fast routine electrolyte testing.', 'Mispa Lyte is an ISE electrolyte analyzer designed for reliable Na/K/Cl measurement with low maintenance and fast turnaround time.', '{"Technology": "ISE", "Typical analytes": "Na / K / Cl", "Use cases": "Routine electrolytes, emergency testing"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='electrolyte-analyzers'), 'ERBA EC 90 Electrolyte Analyzer', 'erba-ec90', 'EC90', 'Erba', 'Next-generation ISE electrolyte analyzer (cartridge system).', 'ERBA EC 90 provides fast electrolyte testing with predictable cost per sample and cartridge-based operation.', '{"Technology":"ISE","Typical analytes":"Na / K / Cl / iCa (model dependent)","Sample types":"Whole blood, serum/plasma, diluted urine"}', 0.00, 0, 1);

-- Clinical Chemistry
INSERT INTO products(category_id,name,slug,sku,brand,short_desc,long_desc,specs_json,price,stock,is_active)
VALUES
((SELECT id FROM categories WHERE slug='clinical-chemistry-analyzers'), 'Mispa CX4 Clinical Chemistry Analyzer', 'mispa-cx4', 'MISPA-CX4', 'Agappe', 'Fully automated random-access clinical chemistry analyzer.', 'Mispa CX4 is a fully automated clinical chemistry analyzer for routine biochemistry with a compact footprint.', '{"Type": "Random access", "Workflow": "Routine biochemistry"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='clinical-chemistry-analyzers'), 'Agappe CXL Pro Plus Clinical Chemistry Analyzer', 'agappe-cxl-pro-plus', 'CXL-PRO-PLUS', 'Agappe', 'High-throughput clinical chemistry analyzer for medium-to-high workload labs.', 'CXL Pro Plus is built for throughput-focused labs and supports common biochemistry assays with robust automation.', '{"Type": "Fully automated", "Use cases": "Routine biochemistry, high workload labs"}', 0.00, 0, 1);

-- Hematology
INSERT INTO products(category_id,name,slug,sku,brand,short_desc,long_desc,specs_json,price,stock,is_active)
VALUES
((SELECT id FROM categories WHERE slug='hematology-analyzers'), 'Mispa HX35 Hematology Analyzer', 'mispa-hx35', 'MISPA-HX35', 'Agappe', 'Compact hematology analyzer for CBC workflows.', 'Mispa HX35 is designed to streamline CBC testing with a compact footprint for small-to-medium laboratories.', '{"Category": "Hematology", "Use cases": "CBC"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='hematology-analyzers'), 'Mispa HX50 Hematology Analyzer', 'mispa-hx50', 'MISPA-HX50', 'Agappe', 'Automated hematology analyzer for medium workload labs.', 'Mispa HX50 is an automated hematology analyzer supporting efficient CBC testing and consistent performance.', '{"Category": "Hematology", "Use cases": "CBC"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='hematology-analyzers'), 'Mispa HX58 Hematology Analyzer', 'mispa-hx58', 'MISPA-HX58', 'Agappe', 'Automated hematology analyzer designed for higher workload CBC testing.', 'Mispa HX58 is built for higher workload hematology with dependable operations and modern workflow.', '{"Category": "Hematology", "Use cases": "CBC"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='hematology-analyzers'), 'ERBA Elite 580 Hematology Analyzer', 'erba-elite-580', 'ELITE580', 'Erba', '5-part differential hematology analyzer for medium workload labs.', 'ERBA Elite 580 is designed for reliable 5-part differential hematology workflows.', '{"Differential": "5-part", "Use cases": "CBC + DIFF"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='hematology-analyzers'), 'ERBA H560 5-Part Hematology Analyzer', 'erba-h560', 'H560', 'Erba', '5-part differential hematology analyzer for dependable routine hematology.', 'ERBA H560 supports routine hematology testing with a 5-part differential and workflow features for busy labs.', '{"Differential": "5-part", "Use cases": "CBC + DIFF"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='hematology-analyzers'), 'ERBA H360 3-Part Hematology Analyzer', 'erba-h360', 'H360', 'Erba', '3-part differential hematology analyzer for routine CBC testing.', 'ERBA H360 supports routine CBC testing and is well-suited for small laboratories or as a secondary unit.', '{"Differential": "3-part", "Use cases": "CBC"}', 0.00, 0, 1);

-- Immunoassay (CLIA)
INSERT INTO products(category_id,name,slug,sku,brand,short_desc,long_desc,specs_json,price,stock,is_active)
VALUES
((SELECT id FROM categories WHERE slug='immunoassay-clia-analyzers'), 'SNIBE MAGLUMI 800 CLIA Analyzer', 'snibe-maglumi-800', 'MAGLUMI800', 'SNIBE', 'Automated chemiluminescence immunoassay system for routine and specialty immunoassays.', 'MAGLUMI 800 is a CLIA analyzer designed for reliable immunoassay workflows and consistent performance.', '{"Technology": "CLIA", "Use cases": "Routine & specialty immunoassays"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='immunoassay-clia-analyzers'), 'SNIBE MAGLUMI X3 CLIA Analyzer', 'snibe-maglumi-x3', 'MAGLUMIX3', 'SNIBE', 'High-performance CLIA analyzer for scalable immunoassay testing.', 'MAGLUMI X3 is built for scalable immunoassay operations in medium-to-high workload laboratories.', '{"Technology": "CLIA", "Use cases": "Medium-to-high workload immunoassays"}', 0.00, 0, 1);

-- Urinalysis
INSERT INTO products(category_id,name,slug,sku,brand,short_desc,long_desc,specs_json,price,stock,is_active)
VALUES
((SELECT id FROM categories WHERE slug='urinalysis-analyzers'), 'ERBA LAURA XL Urine Analyzer', 'erba-laura-xl', 'LAURAXL', 'Erba', 'Automated urine strip analyzer for routine urinalysis.', 'ERBA LAURA XL is a urine strip analyzer designed for efficient urinalysis workflows with clear reporting.', '{"Use cases": "Urine strip analysis", "Workflow": "Routine urinalysis"}', 0.00, 0, 1);

-- HbA1c / HPLC
INSERT INTO products(category_id,name,slug,sku,brand,short_desc,long_desc,specs_json,price,stock,is_active)
VALUES
((SELECT id FROM categories WHERE slug='hba1c-hplc-analyzers'), 'Mispa Maestro HbA1c HPLC Analyzer', 'mispa-maestro', 'MISPA-MAESTRO', 'Agappe', 'HPLC-based HbA1c analyzer for diabetes monitoring workflows.', 'Mispa Maestro is an HPLC HbA1c analyzer designed for reliable HbA1c testing with consistent chromatograms.', '{"Technology": "HPLC", "Assay": "HbA1c"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='hba1c-hplc-analyzers'), 'Laffinite II HbA1c HPLC Analyzer', 'laffinite-ii', 'LAFFINITE-II', 'Agappe', 'HPLC HbA1c analyzer for dependable diabetes monitoring workflows.', 'Laffinite II is an HPLC-based HbA1c analyzer designed to support HbA1c testing in routine laboratory operations.', '{"Technology": "HPLC", "Assay": "HbA1c"}', 0.00, 0, 1);

-- Reagents & Kits (example - expand as you add SKUs)
INSERT INTO products(category_id,name,slug,sku,brand,short_desc,long_desc,specs_json,price,stock,is_active)
VALUES
((SELECT id FROM categories WHERE slug='reagents-kits'), 'SNIBE MAGLUMI TSH CLIA Kit – 100 Tests', 'snibe-maglumi-tsh-100', 'SNB-TSH-100T', 'SNIBE', 'TSH kit for MAGLUMI analyzers.', 'Chemiluminescence immunoassay kit for TSH testing (100 tests).', '{"Platform": "MAGLUMI", "Tests": "100"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='reagents-kits'), 'SNIBE MAGLUMI Free T4 (FT4) CLIA Kit – 100 Tests', 'snibe-maglumi-ft4-100', 'SNB-FT4-100T', 'SNIBE', 'Free T4 kit for MAGLUMI analyzers.', 'Chemiluminescence immunoassay kit for FT4 testing (100 tests).', '{"Platform": "MAGLUMI", "Tests": "100"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='reagents-kits'), 'SNIBE MAGLUMI Vitamin D (25-OH) CLIA Kit – 100 Tests', 'snibe-maglumi-vitd-100', 'SNB-VITD-100T', 'SNIBE', 'Vitamin D kit for MAGLUMI analyzers.', 'Chemiluminescence immunoassay kit for 25-OH Vitamin D testing (100 tests).', '{"Platform": "MAGLUMI", "Tests": "100"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='reagents-kits'), 'SNIBE MAGLUMI Troponin-I CLIA Kit – 100 Tests', 'snibe-maglumi-tni-100', 'SNB-TNI-100T', 'SNIBE', 'Troponin-I kit for MAGLUMI analyzers.', 'Chemiluminescence immunoassay kit for Troponin-I testing (100 tests).', '{"Platform": "MAGLUMI", "Tests": "100"}', 0.00, 0, 1);

-- Consumables & Accessories (examples)
INSERT INTO products(category_id,name,slug,sku,brand,short_desc,long_desc,specs_json,price,stock,is_active)
VALUES
((SELECT id FROM categories WHERE slug='consumables-accessories'), 'Sample Cups (Pack of 500)', 'sample-cups-500', 'SC-500', 'Generic', 'Disposable sample cups for analyzers.', 'Disposable sample cups for routine laboratory use. Pack of 500.', '{"Pack": "500 pcs"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='consumables-accessories'), 'Pipette Tips 200µL (1000 pcs)', 'pipette-tips-200ul-1000', 'PT-200-1000', 'Generic', '200µL tips for routine lab use.', 'Pipette tips for routine lab workflows. 1000 pcs per pack.', '{"Volume": "200 µL", "Pack": "1000"}', 0.00, 0, 1),
((SELECT id FROM categories WHERE slug='consumables-accessories'), 'Thermal Printer Paper Roll (Pack of 10)', 'thermal-paper-roll-10', 'TPR-10', 'Generic', 'Thermal printer paper rolls.', 'Thermal paper roll pack (10).', '{"Pack": "10 rolls"}', 0.00, 0, 1);

-- Product images
-- Default placeholder for everything; real machine photos are added by file assets and inserted below.
INSERT INTO product_images(product_id,image_path,sort_order)
SELECT id, 'assets/no-image.png', 99 FROM products;

-- Real machine photos (included in assets/img/products/)
INSERT INTO product_images(product_id,image_path,sort_order) VALUES
((SELECT id FROM products WHERE slug='mispa-lyte'),'assets/img/products/mispa-lyte.jpg',0),
((SELECT id FROM products WHERE slug='mispa-maestro'),'assets/img/products/mispa-maestro.jpg',0),
((SELECT id FROM products WHERE slug='mispa-cx4'),'assets/img/products/mispa-cx4.jpg',0),
((SELECT id FROM products WHERE slug='mispa-hx35'),'assets/img/products/mispa-hx35.jpg',0),
((SELECT id FROM products WHERE slug='mispa-hx50'),'assets/img/products/mispa-hx50.jpg',0),
((SELECT id FROM products WHERE slug='mispa-hx58'),'assets/img/products/mispa-hx58.jpg',0),
((SELECT id FROM products WHERE slug='laffinite-ii'),'assets/img/products/laffinite-ii.jpg',0),
((SELECT id FROM products WHERE slug='agappe-cxl-pro-plus'),'assets/img/products/agappe-cxl-pro-plus.jpg',0),
((SELECT id FROM products WHERE slug='erba-laura-xl'),'assets/img/products/erba-laura-xl.jpg',0),
((SELECT id FROM products WHERE slug='erba-elite-580'),'assets/img/products/erba-elite-580.jpg',0),
((SELECT id FROM products WHERE slug='erba-h560'),'assets/img/products/erba-h560.jpg',0),
((SELECT id FROM products WHERE slug='erba-h360'),'assets/img/products/erba-h360.jpg',0),
((SELECT id FROM products WHERE slug='erba-ec90'),'assets/img/products/erba-ec90.jpg',0),
((SELECT id FROM products WHERE slug='snibe-maglumi-800'),'assets/img/products/maglumi-800.jpg',0),
((SELECT id FROM products WHERE slug='snibe-maglumi-x3'),'assets/img/products/maglumi-x3.jpg',0);

-- Brochure documents (included in uploads/docs/)
INSERT INTO documents(product_id,title,label,file_path) VALUES
((SELECT id FROM products WHERE slug='mispa-lyte'),'Brochure','Brochure – Mispa Lyte','uploads/docs/mispa-lyte-brochure.pdf'),
((SELECT id FROM products WHERE slug='mispa-maestro'),'Brochure','Brochure – Mispa Maestro','uploads/docs/mispa-maestro-brochure.pdf'),
((SELECT id FROM products WHERE slug='mispa-cx4'),'Brochure','Brochure – Mispa CX4','uploads/docs/mispa-cx4-brochure.pdf'),
((SELECT id FROM products WHERE slug='mispa-hx35'),'Brochure','Brochure – Mispa HX35','uploads/docs/mispa-hx35-brochure.pdf'),
((SELECT id FROM products WHERE slug='mispa-hx50'),'Brochure','Brochure – Mispa HX50','uploads/docs/mispa-hx50-brochure.pdf'),
((SELECT id FROM products WHERE slug='mispa-hx58'),'Brochure','Brochure – Mispa HX58','uploads/docs/mispa-hx58-brochure.pdf'),
((SELECT id FROM products WHERE slug='laffinite-ii'),'Brochure','Brochure – Laffinite II','uploads/docs/laffinite-ii-brochure.pdf'),
((SELECT id FROM products WHERE slug='agappe-cxl-pro-plus'),'Brochure','Brochure – CXL Pro Plus','uploads/docs/agappe-cxl-pro-plus-brochure.pdf'),
((SELECT id FROM products WHERE slug='erba-laura-xl'),'Brochure','Brochure – ERBA LAURA XL','uploads/docs/erba-laura-xl-brochure.pdf'),
((SELECT id FROM products WHERE slug='erba-elite-580'),'Brochure','Brochure – ERBA Elite 580','uploads/docs/erba-elite-580-brochure.pdf'),
((SELECT id FROM products WHERE slug='erba-h560'),'Brochure','Brochure – ERBA H560','uploads/docs/erba-h560-brochure.pdf'),
((SELECT id FROM products WHERE slug='erba-h360'),'Brochure','Brochure – ERBA H360','uploads/docs/erba-h360-brochure.pdf'),
((SELECT id FROM products WHERE slug='erba-ec90'),'Brochure','Brochure – ERBA EC90','uploads/docs/erba-ec90-brochure.pdf'),
((SELECT id FROM products WHERE slug='snibe-maglumi-800'),'Brochure','Brochure – MAGLUMI 800','uploads/docs/maglumi-800-brochure.pdf'),
((SELECT id FROM products WHERE slug='snibe-maglumi-x3'),'Brochure','Brochure – MAGLUMI X3','uploads/docs/maglumi-x3-brochure.pdf');

ALTER TABLE users
  ADD CONSTRAINT fk_users_company_account FOREIGN KEY (company_account_id) REFERENCES company_accounts(id) ON DELETE SET NULL;

ALTER TABLE company_accounts
  ADD CONSTRAINT fk_company_accounts_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS=1;
