-- Separates "Requisition" from "Purchase Order" into two real stages (was previously
-- fused: submitting a requisition created a PO immediately). Adds the recommend-and-approve-
-- with-revision-loop supplier-selection model (Store Manager suggests one supplier + a note,
-- Finance Head approves or requests a revision) and a post-delivery supplier rating stage.

-- Requisition's own comment/revision/audit thread -- mirrors the existing `po_events` pattern
-- already used for purchase orders, now giving requisitions the same kind of timeline.
CREATE TABLE IF NOT EXISTS `requisition_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `requisition_id` INT NOT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `message` TEXT NULL,
    `actor_user_id` INT NULL,
    `role` VARCHAR(20) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_req_events_requisition` (`requisition_id`),
    CONSTRAINT `fk_req_events_requisition` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_req_events_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- The Finance Head's "Request Revision" loop needs one more status than exists today.
ALTER TABLE `requisitions`
    MODIFY COLUMN `status` ENUM('draft','pending_budget_check','budget_rejected','pending_finance_head','revision_requested','approved','rejected','converted_to_po','cancelled') NOT NULL DEFAULT 'pending_budget_check';

-- Stage 9 (Close & Review): Store Manager rates a supplier once a PO is paid/closed.
CREATE TABLE IF NOT EXISTS `supplier_ratings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `po_id` INT NOT NULL,
    `supplier_id` INT NOT NULL,
    `rated_by` INT NOT NULL,
    `on_time` TINYINT(1) NOT NULL DEFAULT 1,
    `fill_rate_percent` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    `quality_rating` TINYINT NOT NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_rating_per_po` (`po_id`),
    KEY `idx_ratings_supplier` (`supplier_id`),
    CONSTRAINT `fk_ratings_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ratings_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
    CONSTRAINT `fk_ratings_rated_by` FOREIGN KEY (`rated_by`) REFERENCES `users` (`user_id`),
    CONSTRAINT `chk_quality_rating` CHECK (`quality_rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
