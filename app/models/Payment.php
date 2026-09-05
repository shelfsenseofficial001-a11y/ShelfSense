<?php
namespace App\Models;

use App\Core\Database;

class Payment
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Real money movement -- created when a PO payment request is approved. */
    public function createForPo($poId, $paymentRequestId, $amount, $method, $referenceNumber, $paidBy, $notes = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO payments (po_id, payment_request_id, amount, method, reference_number, paid_by, paid_at, notes)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$poId, $paymentRequestId, $amount, $method, $referenceNumber, $paidBy, $notes]);
        return (int)$this->db->lastInsertId();
    }

    public function markRemittanceSent($id)
    {
        $stmt = $this->db->prepare("UPDATE payments SET remittance_sent = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getByPoId($poId)
    {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE po_id = ?");
        $stmt->execute([$poId]);
        return $stmt->fetch();
    }
}
