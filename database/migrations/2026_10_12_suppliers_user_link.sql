-- Links a supplier company record to its own login account. Every supplier
-- handler was previously resolving "which supplier am I" by matching
-- users.email to suppliers.email, and silently falling back to treating the
-- raw user_id as the supplier_id when no email matched -- a real account
-- could end up reading/writing another supplier's data under that fallback.
ALTER TABLE suppliers ADD COLUMN user_id INT NULL AFTER id;

UPDATE suppliers s
JOIN users u ON u.email = s.email AND u.role = 'supplier'
SET s.user_id = u.user_id
WHERE s.user_id IS NULL;

ALTER TABLE suppliers ADD UNIQUE KEY uq_suppliers_user_id (user_id);
ALTER TABLE suppliers
    ADD CONSTRAINT fk_suppliers_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL;
