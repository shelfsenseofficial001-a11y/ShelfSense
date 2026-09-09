-- Adds a clearly-labeled "simulated" PayMongo disbursement method for
-- supplier payments. ShelfSense has no registered business behind it, so
-- PayMongo's real Money Movement/Disbursements product (which requires
-- business/KYB verification) is not available -- this records the same
-- payment-tracking data (method, reference, timestamp) a real payout
-- would, without actually moving money anywhere.
ALTER TABLE payments
    MODIFY COLUMN method ENUM('bank_transfer','check','cash','paymongo_simulated','other') NOT NULL DEFAULT 'bank_transfer';
