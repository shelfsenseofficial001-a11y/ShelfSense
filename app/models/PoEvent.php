<?php
namespace App\Models;

use App\Core\Database;

/**
 * Timestamped history timeline for a PO's whole lifecycle (requisition
 * approval through delivery/reconciliation), shown on the PO detail view
 * to all three roles -- filtered by `visibility` the same way goods-receipt
 * and invoice detail are already filtered on that endpoint (Supplier never
 * sees Goods Receipt events, Store Manager never sees Invoice events).
 */
class PoEvent
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function log($poId, $eventType, $description, $visibility = 'all', $actorUserId = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO po_events (po_id, event_type, description, visibility, actor_user_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$poId, $eventType, $description, $visibility, $actorUserId]);
    }

    /** $role: 'supplier' or 'store_manager' hides the matching-restricted entries; anything else sees all. */
    public function getForPo($poId, $role = null)
    {
        $sql = "
            SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) as actor_name
            FROM po_events e
            LEFT JOIN users u ON e.actor_user_id = u.user_id
            WHERE e.po_id = ?
        ";
        $params = [$poId];
        if ($role === 'supplier') {
            $sql .= " AND e.visibility != 'not_supplier'";
        } elseif ($role === 'store_manager') {
            $sql .= " AND e.visibility != 'not_store_manager'";
        }
        $sql .= " ORDER BY e.created_at ASC, e.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
