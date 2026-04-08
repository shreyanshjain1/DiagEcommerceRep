-- Quote approval / rejection flow
ALTER TABLE quotes
  ADD COLUMN approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER status,
  ADD COLUMN approval_note TEXT DEFAULT NULL AFTER approval_status,
  ADD COLUMN approval_decided_at DATETIME DEFAULT NULL AFTER approval_note;

CREATE INDEX idx_quotes_approval_status ON quotes(approval_status);
CREATE INDEX idx_quotes_user_updated ON quotes(user_id, updated_at);
