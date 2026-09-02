-- A Store Manager can now own multiple registers/POS terminals, each
-- allocated and cashed out independently. Previously store_manager_id was
-- UNIQUE on registers, hard-limiting every store to exactly one register.
-- The FK still needs *an* index on the column, so add a plain one before
-- dropping the unique one, in the same statement.
ALTER TABLE registers
    ADD INDEX idx_registers_store_manager_id (store_manager_id),
    DROP INDEX store_manager_id;
