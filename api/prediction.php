<?php
/**
 * Prediction API Endpoint
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../classes/PredictionEngine.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['status' => 'error', 'message' => 'Unauthorized.'], 401);
}

$currentUser = getCurrentUser();
$predictionEngine = new PredictionEngine();

$studentId = null;
if (isset($_GET['student_id'])) {
    $studentId = (int) $_GET['student_id'];
} elseif ($currentUser['role'] === 'student') {
    $studentId = $currentUser['student_id'];
}

if (!$studentId) {
    jsonResponse(['status' => 'error', 'message' => 'Student ID is required.'], 400);
}

if ($currentUser['role'] === 'student' && $currentUser['student_id'] != $studentId) {
    jsonResponse(['status' => 'error', 'message' => 'Forbidden.'], 403);
}

$prediction = $predictionEngine->predictStudentOutcome($studentId);
jsonResponse(['status' => 'success', 'data' => $prediction]);
