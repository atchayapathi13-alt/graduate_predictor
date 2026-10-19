<?php
/**
 * Recommendations API Endpoint
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../classes/RecommendationEngine.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['status' => 'error', 'message' => 'Unauthorized.'], 401);
}

$currentUser = getCurrentUser();
$recEngine = new RecommendationEngine();

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

$recommendations = $recEngine->generateStudentRecommendations($studentId);
jsonResponse(['status' => 'success', 'count' => count($recommendations), 'data' => $recommendations]);
