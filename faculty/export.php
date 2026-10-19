<?php
/**
 * Faculty Export Cohort to CSV
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

requireRole(['faculty', 'admin']);
$db = getDB();

$sql = "
    SELECT s.student_id, u.name, u.email, u.phone, d.dept_name, d.dept_code,
           s.degree, s.academic_year, s.graduation_year,
           ar.tenth_percentage, ar.twelfth_percentage, ar.diploma_percentage,
           ar.current_cgpa, ar.current_semester, ar.num_backlogs, ar.cleared_backlogs,
           ar.attendance_percentage,
           pa.academic_score, pa.technical_score, pa.career_readiness_score,
           pa.soft_skills_score, pa.extracurricular_score, pa.overall_score,
           pr.category as prediction_category, pr.confidence_score
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    LEFT JOIN performance_analysis pa ON s.id = pa.student_id
    LEFT JOIN predictions pr ON s.id = pr.student_id
    ORDER BY s.id ASC
";

$students = $db->query($sql)->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Cohort_Analysis_Export_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');

// Header Row
fputcsv($output, [
    'Student ID', 'Full Name', 'Email', 'Phone', 'Department', 'Department Code',
    'Degree', 'Academic Batch', 'Graduation Year',
    '10th %', '12th %', 'Diploma %',
    'Current CGPA', 'Current Semester', 'Active Backlogs', 'Cleared Backlogs',
    'Attendance %',
    'Academic Score (%)', 'Technical Score (%)', 'Career Readiness Score (%)',
    'Soft Skills Score (%)', 'Extracurricular Score (%)', 'Overall Performance Score (%)',
    'Prediction Outcome Tier', 'Confidence Index (%)'
]);

foreach ($students as $row) {
    fputcsv($output, [
        $row['student_id'],
        $row['name'],
        $row['email'],
        $row['phone'],
        $row['dept_name'],
        $row['dept_code'],
        $row['degree'],
        $row['academic_year'],
        $row['graduation_year'],
        $row['tenth_percentage'],
        $row['twelfth_percentage'] ?: 'N/A',
        $row['diploma_percentage'] ?: 'N/A',
        $row['current_cgpa'],
        $row['current_semester'],
        $row['num_backlogs'],
        $row['cleared_backlogs'],
        $row['attendance_percentage'],
        $row['academic_score'] ?? 0,
        $row['technical_score'] ?? 0,
        $row['career_readiness_score'] ?? 0,
        $row['soft_skills_score'] ?? 0,
        $row['extracurricular_score'] ?? 0,
        $row['overall_score'] ?? 0,
        $row['prediction_category'] ?? 'Pending',
        $row['confidence_score'] ?? 0
    ]);
}

fclose($output);
exit;
