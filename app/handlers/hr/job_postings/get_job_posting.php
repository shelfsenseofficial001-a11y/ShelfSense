<?php
// app/handlers/hr/job_postings/get_job_posting.php

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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    Response::error('Invalid job posting ID', 400);
}

try {
    $model = new JobPosting();
    $posting = $model->getById($id);
    if (!$posting) {
        Response::notFound('Job posting not found');
    }
    $posting['lineage'] = $model->getLineage($id);

    Response::success(['posting' => $posting], 'Job posting fetched');

} catch (Exception $e) {
    error_log('get_job_posting.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
