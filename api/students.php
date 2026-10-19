<?php
/**
 * Students API Endpoint
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

header('Content-Type: application/json; charset=utf-8');

// Access requires login
if (!isLoggedIn()) {
    jsonResponse(['status' => 'error', 'message' => 'Unauthorized. Please login.'], 401);
}

$currentUser = getCurrentUser();
$db = getDB();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Single student query
    if (isset($_GET['id'])) {
        $studentId = (int) $_GET['id'];
        
        // If student is logged in, ensure they can only query themselves unless faculty/admin
        if ($currentUser['role'] === 'student' && $currentUser['student_id'] != $studentId) {
            jsonResponse(['status' => 'error', 'message' => 'Forbidden.'], 403);
        }

        $stmt = $db->prepare("
            SELECT s.*, u.name, u.email, u.phone, d.dept_name, d.dept_code,
                   ar.tenth_percentage, ar.twelfth_percentage, ar.diploma_percentage,
                   ar.current_cgpa, ar.current_semester, ar.num_backlogs, ar.cleared_backlogs,
                   ar.attendance_percentage,
                   pa.academic_score, pa.technical_score, pa.career_readiness_score,
                   pa.soft_skills_score, pa.extracurricular_score, pa.overall_score,
                   pr.category as prediction_category, pr.confidence_score, pr.prediction_notes
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN departments d ON s.department_id = d.id
            LEFT JOIN academic_records ar ON s.id = ar.student_id
            LEFT JOIN performance_analysis pa ON s.id = pa.student_id
            LEFT JOIN predictions pr ON s.id = pr.student_id
            WHERE s.id = ?
        ");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();

        if (!$student) {
            jsonResponse(['status' => 'error', 'message' => 'Student not found.'], 404);
        }

        jsonResponse(['status' => 'success', 'data' => $student]);
    }

    // List of students (for faculty/admin)
    if ($currentUser['role'] === 'student') {
        jsonResponse(['status' => 'error', 'message' => 'Forbidden.'], 403);
    }

    $where = ["1=1"];
    $params = [];

    if (!empty($_GET['dept'])) {
        $where[] = "d.id = ?";
        $params[] = (int) $_GET['dept'];
    }

    if (!empty($_GET['readiness'])) {
        $where[] = "pr.category = ?";
        $params[] = trim($_GET['readiness']);
    }

    if (isset($_GET['min_cgpa']) && is_numeric($_GET['min_cgpa'])) {
        $where[] = "ar.current_cgpa >= ?";
        $params[] = (float) $_GET['min_cgpa'];
    }

    if (isset($_GET['min_attendance']) && is_numeric($_GET['min_attendance'])) {
        $where[] = "ar.attendance_percentage >= ?";
        $params[] = (float) $_GET['min_attendance'];
    }

    if (!empty($_GET['internship'])) {
        if ($_GET['internship'] === 'yes') {
            $where[] = "EXISTS (SELECT 1 FROM internships i WHERE i.student_id = s.id AND i.has_internship = 1)";
        } elseif ($_GET['internship'] === 'no') {
            $where[] = "NOT EXISTS (SELECT 1 FROM internships i WHERE i.student_id = s.id AND i.has_internship = 1)";
        }
    }

    if (!empty($_GET['search'])) {
        $term = "%" . trim($_GET['search']) . "%";
        $where[] = "(u.name LIKE ? OR s.student_id LIKE ? OR u.email LIKE ?)";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $sql = "
        SELECT s.id, s.student_id, s.degree, s.graduation_year,
               u.name, u.email, d.dept_code, d.dept_name,
               ar.current_cgpa, ar.attendance_percentage, ar.num_backlogs,
               pa.overall_score, pa.academic_score, pa.technical_score,
               pr.category as prediction_category, pr.confidence_score
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN departments d ON s.department_id = d.id
        LEFT JOIN academic_records ar ON s.id = ar.student_id
        LEFT JOIN performance_analysis pa ON s.id = pa.student_id
        LEFT JOIN predictions pr ON s.id = pr.student_id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY s.id ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    jsonResponse(['status' => 'success', 'count' => count($students), 'data' => $students]);
}

jsonResponse(['status' => 'error', 'message' => 'Method not allowed.'], 405);
