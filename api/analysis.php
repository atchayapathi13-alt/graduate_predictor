<?php
/**
 * Analysis API Endpoint
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../classes/AnalysisEngine.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['status' => 'error', 'message' => 'Unauthorized.'], 401);
}

$currentUser = getCurrentUser();
$analysisEngine = new AnalysisEngine();

$studentId = null;
if (isset($_GET['student_id'])) {
    $studentId = (int) $_GET['student_id'];
} elseif ($currentUser['role'] === 'student') {
    $studentId = $currentUser['student_id'];
}

if (!$studentId) {
    jsonResponse(['status' => 'error', 'message' => 'Student ID is required.'], 400);
}

// Security: students cannot view other students' analysis
if ($currentUser['role'] === 'student' && $currentUser['student_id'] != $studentId) {
    jsonResponse(['status' => 'error', 'message' => 'Forbidden.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['recalculate'])) {
    $result = $analysisEngine->calculateStudentAnalysis($studentId);
    jsonResponse(['status' => 'success', 'message' => 'Analysis calculated successfully.', 'data' => $result]);
}

$result = $analysisEngine->calculateStudentAnalysis($studentId);
jsonResponse(['status' => 'success', 'data' => $result]);
