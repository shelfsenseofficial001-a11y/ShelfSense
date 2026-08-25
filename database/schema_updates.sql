-- ============================================================
-- ShelfSense — incremental schema updates
-- ============================================================
-- The project has no migration system; database/shelfsense.sql is a
-- point-in-time dump and is NOT re-exported after every change. This file
-- is an append-only, chronological log of every ALTER/CREATE statement
-- applied directly to the local development database after that dump,
-- so the real schema can be reconstructed as: shelfsense.sql + this file
-- (in order). Each block is safe to re-run only once (no IF NOT EXISTS
-- guards are relied upon — check first if replaying against a fresh copy).
-- ============================================================

-- --------------------------------------------------------------
-- Finance Staff implementation — payment integrity constraints
-- --------------------------------------------------------------

-- At most one ACTIVE (status = 'pending') payment request per requisition.
-- Generated column collapses to NULL once a request is approved/rejected,
-- so the unique key only ever blocks a second *concurrent* pending request.
ALTER TABLE payment_requests
  ADD COLUMN active_requisition_lock INT
    GENERATED ALWAYS AS (CASE WHEN status = 'pending' THEN requisition_id ELSE NULL END) STORED,
  ADD UNIQUE KEY uniq_active_requisition (active_requisition_lock);

-- At most one payment record per supplier invoice — hard guarantee against
-- a duplicate payment being recorded for the same invoice.
ALTER TABLE payments
  ADD UNIQUE KEY uniq_invoice_payment (supplier_invoice_id);

-- --------------------------------------------------------------
-- Finance Head implementation — approval justification + budget audit trail
-- --------------------------------------------------------------

-- Finance Head's own approval notes / required over-budget justification,
-- distinct from Finance Staff's `notes` and the `rejection_reason` column.
ALTER TABLE payment_requests
  ADD COLUMN approval_notes TEXT NULL AFTER budget_exceeded_reason;

-- Truthful history of every budget allocation created/adjusted by Finance
-- Head: previous amount, new amount, and what was already used/reserved at
-- the moment of the change, so used_budget is never overwritten or lost.
CREATE TABLE budget_adjustments (
  id INT(11) NOT NULL AUTO_INCREMENT,
  budget_id INT(11) NOT NULL,
  department VARCHAR(20) NOT NULL,
  month_year VARCHAR(7) NOT NULL,
  previous_allocated DECIMAL(12,2) NOT NULL,
  new_allocated DECIMAL(12,2) NOT NULL,
  adjustment_amount DECIMAL(12,2) NOT NULL,
  used_at_adjustment DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  reserved_at_adjustment DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  adjusted_by INT(11) NOT NULL,
  reason TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_department_month (department, month_year),
  KEY idx_adjusted_by (adjusted_by),
  KEY idx_created_at (created_at),
  CONSTRAINT fk_budget_adjustments_budget FOREIGN KEY (budget_id) REFERENCES budgets(id),
  CONSTRAINT fk_budget_adjustments_user FOREIGN KEY (adjusted_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
