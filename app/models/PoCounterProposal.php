<?php
namespace App\Models;

use App\Core\Database;

class PoCounterProposal
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Always called from within the caller's own transaction (respond.php
     * wraps this alongside the PO status update) -- no begin/commit here,
     * since PDO doesn't support nested transactions.
     */
    public function create($poId, $proposedBy, $reason, $items)
    {
        $stmt = $this->db->prepare("INSERT INTO po_counter_proposals (po_id, proposed_by, reason) VALUES (?, ?, ?)");
        $stmt->execute([$poId, $proposedBy, $reason]);
        $proposalId = (int)$this->db->lastInsertId();

        $itemStmt = $this->db->prepare("
            INSERT INTO po_counter_proposal_items (proposal_id, po_item_id, proposed_quantity, proposed_unit_price, proposed_delivery_date, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($items as $item) {
            $itemStmt->execute([
                $proposalId,
                $item['po_item_id'],
                $item['proposed_quantity'] ?? null,
                $item['proposed_unit_price'] ?? null,
                $item['proposed_delivery_date'] ?? null,
                $item['notes'] ?? null,
            ]);
        }

        return $proposalId;
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT pcp.*, CONCAT(u.first_name, ' ', u.last_name) as proposed_by_name
            FROM po_counter_proposals pcp
            JOIN users u ON pcp.proposed_by = u.user_id
            WHERE pcp.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getItems($proposalId)
    {
        $stmt = $this->db->prepare("
            SELECT pcpi.*, poi.quantity as current_quantity, poi.unit_price as current_unit_price,
                   p.name as store_product_name
            FROM po_counter_proposal_items pcpi
            JOIN purchase_order_items poi ON pcpi.po_item_id = poi.id
            JOIN products p ON poi.store_product_id = p.id
            WHERE pcpi.proposal_id = ?
        ");
        $stmt->execute([$proposalId]);
        return $stmt->fetchAll();
    }

    public function getWithItems($id)
    {
        $proposal = $this->getById($id);
        if (!$proposal) {
            return null;
        }
        $proposal['items'] = $this->getItems($id);
        return $proposal;
    }

    public function getLatestPendingForPo($poId)
    {
        $stmt = $this->db->prepare("SELECT * FROM po_counter_proposals WHERE po_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$poId]);
        return $stmt->fetch();
    }

    public function respond($id, $status, $respondedBy)
    {
        $stmt = $this->db->prepare("UPDATE po_counter_proposals SET status = ?, responded_by = ?, responded_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $respondedBy, $id]);
    }
}
