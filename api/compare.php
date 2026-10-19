<?php
/**
 * Student Comparison API Endpoint
 * Provides multi-student comparative metrics for Chart.js rendering
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['status' => 'error', 'message' => 'Unauthorized.'], 401);
}

$currentUser = getCurrentUser();
if ($currentUser['role'] === 'student') {
    jsonResponse(['status' => 'error', 'message' => 'Forbidden.'], 403);
}

$db = getDB();
$idsParam = $_GET['ids'] ?? '';
$ids = array_filter(array_map('intval', explode(',', $idsParam)));

if (empty($ids)) {
    jsonResponse(['status' => 'error', 'message' => 'Please provide at least 2 student IDs (e.g. ?ids=1,2).'], 400);
}

// Limit comparison to maximum 5 students for optimal visual clarity
$ids = array_slice($ids, 0, 5);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$sql = "
    SELECT s.id, s.student_id, u.name, d.dept_code,
           ar.current_cgpa, ar.attendance_percentage, ar.num_backlogs,
           pa.academic_score, pa.technical_score, pa.career_readiness_score,
           pa.soft_skills_score, pa.extracurricular_score, pa.overall_score,
           pr.category as prediction_category,
           (SELECT count(*) FROM projects p WHERE p.student_id = s.id) as project_count,
           (SELECT count(*) FROM certifications c WHERE c.student_id = s.id) as cert_count,
           (SELECT count(*) FROM internships i WHERE i.student_id = s.id AND i.has_internship = 1) as internship_count
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    LEFT JOIN performance_analysis pa ON s.id = pa.student_id
    LEFT JOIN predictions pr ON s.id = pr.student_id
    WHERE s.id IN ($placeholders)
";

$stmt = $db->prepare($sql);
$stmt->execute($ids);
$students = $stmt->fetchAll();

if (count($students) < 2) {
    jsonResponse(['status' => 'error', 'message' => 'Found fewer than 2 valid students for comparison.'], 404);
}

jsonResponse([
    'status' => 'success',
    'count' => count($students),
    'data' => $students
]);
