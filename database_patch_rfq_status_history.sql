-- RFQ status history / timeline foundation
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
