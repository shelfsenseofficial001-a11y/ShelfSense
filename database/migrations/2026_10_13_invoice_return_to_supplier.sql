-- Replaces the Finance Staff "Override" action (which just force-marked a
-- mismatched invoice reconciled despite the variance, with no actual fix)
-- with a real "Return to Supplier" flow: Finance sends the invoice back
-- with a reason, the Supplier corrects and resubmits it. Since invoices.po_id
-- is UNIQUE (one invoice per PO), a returned invoice is revised in place
-- rather than replaced by a new row -- this column holds why it was
-- returned, cleared again once the Supplier resubmits.

ALTER TABLE `invoices` ADD COLUMN `rejection_reason` TEXT NULL AFTER `notes`;
