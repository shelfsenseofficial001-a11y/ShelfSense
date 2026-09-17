<?php
// app/handlers/pos/get_deals.php
// Active bundle deals for the POS product grid. A deal is its own
// sellable line item (see create_order.php) -- ringing it up doesn't
// touch its component products' order_items rows, just their stock.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Deal.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Deal;

header('Content-Type: application/json');

if (!Auth::posCheck() && !(Auth::check() && (Auth::isEmployee() || Auth::isSuperAdmin() || Auth::isStoreManager()))) {
    Response::unauthorized('Please login to access this resource');
}

try {
    $dealModel = new Deal();
    $deals = $dealModel->getAll(true);

    foreach ($deals as &$deal) {
        $deal['price'] = (float)$deal['price'];
        $deal['image_url'] = $deal['image_path']
            ? '/ShelfSense/public/' . $deal['image_path']
            : '/ShelfSense/public/assets/images/placeholder-product.png';

        // A bundle can only be sold while every component still has
        // enough stock for the quantities the bundle requires.
        $maxAvailable = null;
        foreach ($deal['items'] as $item) {
            $possible = intdiv((int)$item['stock_quantity'], (int)$item['quantity']);
            if ($maxAvailable === null || $possible < $maxAvailable) {
                $maxAvailable = $possible;
            }
        }
        $deal['available'] = $maxAvailable ?? 0;
    }

    Response::success(['deals' => $deals], 'Deals fetched successfully');
} catch (Exception $e) {
    error_log('get_deals.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
