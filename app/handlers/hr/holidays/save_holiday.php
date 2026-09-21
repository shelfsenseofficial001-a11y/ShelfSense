<?php
// app/handlers/hr/holidays/save_holiday.php
// Creates a new holiday, or updates an existing one if "id" is present.

require_once __DIR__ . '/../../../models/Holiday.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Holiday;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
// Holidays are a shared reference used by every HR user's payday-warning
// check, but only Owner may decide what actually counts as a holiday.
if (!Auth::isOwner() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Owner role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : null;
$date = isset($input['holiday_date']) ? trim($input['holiday_date']) : '';
$name = isset($input['name']) ? trim($input['name']) : '';
$type = isset($input['type']) ? trim($input['type']) : 'special_non_working';

if (empty($date) || !DateTime::createFromFormat('Y-m-d', $date)) {
    Response::error('A valid date is required.', 400);
}
if (empty($name)) {
    Response::error('A name is required.', 400);
}
if (!in_array($type, ['regular', 'special_non_working'], true)) {
    Response::error('Invalid holiday type.', 400);
}

try {
    $holidayModel = new Holiday();
    if ($id) {
        $holidayModel->update($id, $date, $name, $type);
        Response::success(['id' => $id], 'Holiday updated successfully');
    } else {
        $existing = $holidayModel->getByDate($date);
        if ($existing) {
            Response::error('A holiday is already recorded on this date.', 400);
        }
        $holidayModel->create($date, $name, $type, Auth::userId());
        Response::success([], 'Holiday added successfully');
    }
} catch (Exception $e) {
    error_log('save_holiday.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
