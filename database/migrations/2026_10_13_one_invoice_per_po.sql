-- Supplier submits exactly one invoice per PO (1:1), not an open-ended series as
-- the shipment trickles in. Invoicing only unlocks once the PO is fully, cleanly
-- received (status 'received', no open RMA/Backorder) -- the single invoice always
-- represents the complete, final bill. Enforced here as a hard DB constraint, on
-- top of the application-level guard in invoices/create.php, so a race condition
-- or a future code path can never silently create a second invoice for the same PO.

ALTER TABLE `invoices` ADD UNIQUE KEY `uniq_invoice_po` (`po_id`);
