<?php
// app/handlers/shared/search_procurement_history.php
// Unified search/tracking across every procurement document: Requisitions,
// Purchase Orders, Goods Receipts, and Invoices -- by number (partial
// match) and/or a date range, optionally narrowed to one document type.
// Each row carries enough ids to open that document's existing detail view.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isFinanceStaff() && !Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied.');
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to = isset($_GET['to']) ? trim($_GET['to']) : '';
$limit = isset($_GET['limit']) ? min(200, max(1, intval($_GET['limit']))) : 50;

$validTypes = ['requisition', 'po', 'gr', 'invoice'];
if ($type !== '' && !in_array($type, $validTypes, true)) {
    Response::error('Invalid type filter', 400);
}

$db = Database::getInstance()->getConnection();
$like = '%' . $query . '%';

try {
    $results = [];

    if ($type === '' || $type === 'requisition') {
        $sql = "
            SELECT r.id, r.requisition_number as number, r.status, r.created_at as doc_date, s.company_name as party_name
            FROM requisitions r
            LEFT JOIN suppliers s ON r.preferred_supplier_id = s.id
            WHERE (? = '' OR r.requisition_number LIKE ?)
              AND (? = '' OR r.created_at >= ?)
              AND (? = '' OR r.created_at <= ?)
            ORDER BY r.created_at DESC
            LIMIT ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$query, $like, $from, $from, $to, $to . ' 23:59:59', $limit]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'type' => 'requisition',
                'id' => (int)$row['id'],
                'number' => $row['number'],
                'status' => $row['status'],
                'date' => $row['doc_date'],
                'party_name' => $row['party_name'],
            ];
        }
    }

    if ($type === '' || $type === 'po') {
        $sql = "
            SELECT po.id, po.po_number as number, po.status, po.created_at as doc_date, s.company_name as party_name
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            WHERE (? = '' OR po.po_number LIKE ?)
              AND (? = '' OR po.created_at >= ?)
              AND (? = '' OR po.created_at <= ?)
            ORDER BY po.created_at DESC
            LIMIT ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$query, $like, $from, $from, $to, $to . ' 23:59:59', $limit]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'type' => 'po',
                'id' => (int)$row['id'],
                'number' => $row['number'],
                'status' => $row['status'],
                'date' => $row['doc_date'],
                'party_name' => $row['party_name'],
            ];
        }
    }

    if ($type === '' || $type === 'gr') {
        $sql = "
            SELECT gr.id, gr.gr_number as number, gr.po_id, po.po_number, gr.receipt_date as doc_date, CONCAT(u.first_name, ' ', u.last_name) as party_name
            FROM goods_receipts gr
            JOIN purchase_orders po ON gr.po_id = po.id
            JOIN users u ON gr.received_by = u.user_id
            WHERE (? = '' OR gr.gr_number LIKE ? OR po.po_number LIKE ?)
              AND (? = '' OR gr.receipt_date >= ?)
              AND (? = '' OR gr.receipt_date <= ?)
            ORDER BY gr.created_at DESC
            LIMIT ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$query, $like, $like, $from, $from, $to, $to, $limit]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'type' => 'gr',
                'id' => (int)$row['po_id'],
                'number' => $row['number'],
                'status' => null,
                'date' => $row['doc_date'],
                'party_name' => $row['party_name'],
                'related_number' => $row['po_number'],
            ];
        }
    }

    if ($type === '' || $type === 'invoice') {
        $sql = "
            SELECT i.id, i.invoice_number as number, i.match_status as status, i.po_id, po.po_number, i.invoice_date as doc_date, s.company_name as party_name
            FROM invoices i
            JOIN purchase_orders po ON i.po_id = po.id
            JOIN suppliers s ON i.supplier_id = s.id
            WHERE (? = '' OR i.invoice_number LIKE ? OR po.po_number LIKE ?)
              AND (? = '' OR i.invoice_date >= ?)
              AND (? = '' OR i.invoice_date <= ?)
            ORDER BY i.created_at DESC
            LIMIT ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$query, $like, $like, $from, $from, $to, $to, $limit]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'type' => 'invoice',
                'id' => (int)$row['po_id'],
                'number' => $row['number'],
                'status' => $row['status'],
                'date' => $row['doc_date'],
                'party_name' => $row['party_name'],
                'related_number' => $row['po_number'],
            ];
        }
    }

    usort($results, function ($a, $b) {
        return strcmp($b['date'] ?? '', $a['date'] ?? '');
    });

    Response::success(['results' => array_slice($results, 0, $limit)], count($results) . ' result(s) found');
} catch (Exception $e) {
    error_log('search_procurement_history.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
