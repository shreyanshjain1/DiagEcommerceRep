-- Patch for Option A Formal Quotation fields (run once on existing database)
-- Adds overhead / other / installation expenses + terms + send tracking

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
