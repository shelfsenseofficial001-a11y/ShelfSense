<?php
// app/handlers/shared/get_invoice.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Invoice.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Invoice;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceStaff() && !Auth::isFinanceHead() && !Auth::isSupplier() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied.');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    Response::error('Invalid invoice id', 400);
}

try {
    $invoiceModel = new Invoice();
    $invoice = $invoiceModel->getWithItems($id);
    if (!$invoice) {
        Response::notFound('Invoice not found');
    }

    if (Auth::isSupplier() && !Auth::isSuperAdmin()) {
        $supplierId = Auth::supplierId();
        if (!$supplierId || $supplierId !== (int)$invoice['supplier_id']) {
            Response::forbidden('This invoice does not belong to you.');
        }
    }

    Response::success($invoice, 'Invoice fetched successfully');
} catch (Exception $e) {
    error_log('shared/get_invoice.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
