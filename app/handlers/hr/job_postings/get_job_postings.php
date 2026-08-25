<?php
// app/handlers/hr/job_postings/get_job_postings.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/JobPosting.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\JobPosting;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}
if (!Auth::isHR() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. HR role required.');
}

$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
$status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$mine = !empty($_GET['mine']);

$filters = ['status' => $status, 'search' => $search];
if ($mine) {
    $filters['created_by'] = Auth::userId();
}

try {
    $model = new JobPosting();
    $data = $model->getAll($page, $limit, $filters);
    $counts = $model->getStatusCounts();

    Response::success([
        'postings' => $data['postings'],
        'pagination' => $data['pagination'],
        'counts' => $counts
    ], 'Job postings fetched');

} catch (Exception $e) {
    error_log('get_job_postings.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
