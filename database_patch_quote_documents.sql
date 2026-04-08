-- Stored quotation documents / generated snapshots
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
