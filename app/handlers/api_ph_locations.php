<?php
// app/handlers/api_ph_locations.php
//
// Public, read-only proxy for the career application form's Province ->
// City/Municipality -> Barangay cascade (see views/pages/auth/apply.php).
// No auth required -- same trust level as the rest of the public apply page.

require_once __DIR__ . '/../core/PsgcClient.php';

use App\Core\PsgcClient;

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

try {
    if ($type === 'provinces') {
        echo json_encode(['success' => true, 'data' => PsgcClient::getProvinces()]);
        exit;
    }

    if ($type === 'cities') {
        $provinceCode = trim($_GET['province_code'] ?? '');
        if ($provinceCode === '') {
            echo json_encode(['success' => false, 'message' => 'province_code is required']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => PsgcClient::getCitiesByProvince($provinceCode)]);
        exit;
    }

    if ($type === 'barangays') {
        $cityCode = trim($_GET['city_code'] ?? '');
        if ($cityCode === '') {
            echo json_encode(['success' => false, 'message' => 'city_code is required']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => PsgcClient::getBarangaysByCity($cityCode)]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown or missing type. Use provinces, cities, or barangays.']);
} catch (Exception $e) {
    error_log('api_ph_locations.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Philippine location data is temporarily unavailable. Please try again shortly.']);
}
