<?php
/**
 * Department & System Analytics API Endpoint
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

// 1. Overall Metrics
$totalStudents = (int) $db->query("SELECT count(*) FROM students")->fetchColumn();
$totalFaculty  = (int) $db->query("SELECT count(*) FROM faculty")->fetchColumn();
$totalDepts    = (int) $db->query("SELECT count(*) FROM departments")->fetchColumn();

// 2. Average Metrics
$stmtAvg = $db->query("
    SELECT 
        ROUND(AVG(ar.current_cgpa), 2) as avg_cgpa,
        ROUND(AVG(ar.attendance_percentage), 1) as avg_attendance,
        ROUND(AVG(pa.technical_score), 1) as avg_technical,
        ROUND(AVG(pa.overall_score), 1) as avg_overall
    FROM students s
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    LEFT JOIN performance_analysis pa ON s.id = pa.student_id
");
$averages = $stmtAvg->fetch();

// 3. Readiness Distribution
$stmtDist = $db->query("
    SELECT category, count(*) as count 
    FROM predictions 
    GROUP BY category
");
$rawDist = $stmtDist->fetchAll(PDO::FETCH_KEY_PAIR);
$readinessDistribution = [
    'High Readiness'     => (int) ($rawDist['High Readiness'] ?? 0),
    'Moderate Readiness' => (int) ($rawDist['Moderate Readiness'] ?? 0),
    'Needs Improvement'  => (int) ($rawDist['Needs Improvement'] ?? 0)
];

// 4. Department-Wise Comparison
$stmtDept = $db->query("
    SELECT d.dept_code, d.dept_name,
           count(s.id) as student_count,
           ROUND(AVG(ar.current_cgpa), 2) as avg_cgpa,
           ROUND(AVG(ar.attendance_percentage), 1) as avg_attendance,
           ROUND(AVG(pa.overall_score), 1) as avg_overall
    FROM departments d
    LEFT JOIN students s ON d.id = s.department_id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    LEFT JOIN performance_analysis pa ON s.id = pa.student_id
    GROUP BY d.id
    ORDER BY d.id ASC
");
$deptMetrics = $stmtDept->fetchAll();

// 5. CGPA Range Buckets for histogram/bar chart
$stmtCgpa = $db->query("
    SELECT 
        SUM(CASE WHEN ar.current_cgpa >= 9.0 THEN 1 ELSE 0 END) as '9_10',
        SUM(CASE WHEN ar.current_cgpa >= 8.0 AND ar.current_cgpa < 9.0 THEN 1 ELSE 0 END) as '8_8.9',
        SUM(CASE WHEN ar.current_cgpa >= 7.0 AND ar.current_cgpa < 8.0 THEN 1 ELSE 0 END) as '7_7.9',
        SUM(CASE WHEN ar.current_cgpa >= 6.0 AND ar.current_cgpa < 7.0 THEN 1 ELSE 0 END) as '6_6.9',
        SUM(CASE WHEN ar.current_cgpa < 6.0 THEN 1 ELSE 0 END) as 'below_6'
    FROM academic_records ar
");
$cgpaBuckets = $stmtCgpa->fetch();

jsonResponse([
    'status' => 'success',
    'summary' => [
        'total_students' => $totalStudents,
        'total_faculty'  => $totalFaculty,
        'total_depts'    => $totalDepts,
        'averages'       => $averages
    ],
    'readiness_distribution' => $readinessDistribution,
    'department_metrics'     => $deptMetrics,
    'cgpa_buckets'           => $cgpaBuckets
]);
