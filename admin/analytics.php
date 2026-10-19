<?php
/**
 * Administrator Deep-Dive System Analytics
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Institutional System Analytics';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

requireRole('admin');
$db = getDB();

// 1. Table Statistics
$tableStats = [
    'Students'                 => (int) $db->query("SELECT count(*) FROM students")->fetchColumn(),
    'Faculty'                  => (int) $db->query("SELECT count(*) FROM faculty")->fetchColumn(),
    'Departments'              => (int) $db->query("SELECT count(*) FROM departments")->fetchColumn(),
    'Academic Records'         => (int) $db->query("SELECT count(*) FROM academic_records")->fetchColumn(),
    'Recorded Skills'          => (int) $db->query("SELECT count(*) FROM student_skills")->fetchColumn(),
    'Projects Registered'      => (int) $db->query("SELECT count(*) FROM projects")->fetchColumn(),
    'Verified Certifications'  => (int) $db->query("SELECT count(*) FROM certifications")->fetchColumn(),
    'Completed Internships'    => (int) $db->query("SELECT count(*) FROM internships WHERE has_internship = 1")->fetchColumn(),
    'Extracurricular Events'   => (int) $db->query("SELECT count(*) FROM extracurricular_activities")->fetchColumn(),
    'Performance Analyses'     => (int) $db->query("SELECT count(*) FROM performance_analysis")->fetchColumn(),
    'Predictions Formulated'   => (int) $db->query("SELECT count(*) FROM predictions")->fetchColumn(),
    'Generated Recommendations'=> (int) $db->query("SELECT count(*) FROM recommendations")->fetchColumn()
];

// 2. Department Deep-Dive Table
$stmtDepts = $db->query("
    SELECT d.dept_code, d.dept_name,
           count(s.id) as student_count,
           ROUND(AVG(ar.current_cgpa), 2) as avg_cgpa,
           ROUND(AVG(ar.attendance_percentage), 1) as avg_attendance,
           ROUND(AVG(pa.academic_score), 1) as avg_academic,
           ROUND(AVG(pa.technical_score), 1) as avg_technical,
           ROUND(AVG(pa.career_readiness_score), 1) as avg_career,
           ROUND(AVG(pa.overall_score), 1) as avg_overall,
           SUM(CASE WHEN pr.category = 'High Readiness' THEN 1 ELSE 0 END) as high_count,
           SUM(CASE WHEN pr.category = 'Needs Improvement' THEN 1 ELSE 0 END) as low_count
    FROM departments d
    LEFT JOIN students s ON d.id = s.department_id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    LEFT JOIN performance_analysis pa ON s.id = pa.student_id
    LEFT JOIN predictions pr ON s.id = pr.student_id
    GROUP BY d.id
    ORDER BY avg_overall DESC
");
$deptDeepDive = $stmtDepts->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">Institutional Prediction Statistics & Data Audit</h4>
    <p class="text-muted small mb-0">System-wide analytical overview, department league performance, and database integrity metrics</p>
</div>

<!-- Database Metrics Cards Grid -->
<div class="content-card mb-4">
    <div class="content-card-header">
        <h6 class="fw-bold mb-0"><i class="fas fa-database me-2 text-primary"></i>System Database Table Volume Metrics</h6>
    </div>
    <div class="content-card-body p-4">
        <div class="row g-3 text-center">
            <?php foreach ($tableStats as $label => $cnt): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-3 bg-light rounded-3 border">
                        <small class="text-muted d-block text-truncate"><?= sanitize($label) ?></small>
                        <h4 class="fw-bold mb-0 text-dark"><?= $cnt ?></h4>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Department League Table -->
<div class="content-card mb-4">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="fas fa-ranking-star me-2 text-warning"></i>Department Cohort Performance Breakdown</h6>
        <span class="badge bg-light text-dark border">Sorted by Overall Score</span>
    </div>
    <div class="content-card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-hover mb-0">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Cohort</th>
                        <th>Avg CGPA</th>
                        <th>Avg Attendance</th>
                        <th>Academic</th>
                        <th>Technical</th>
                        <th>Career</th>
                        <th>Avg Overall</th>
                        <th>High / At-Risk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deptDeepDive as $dp): ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?= sanitize($dp['dept_name']) ?></strong>
                                <span class="badge bg-light text-dark border font-monospace ms-1"><?= sanitize($dp['dept_code']) ?></span>
                            </td>
                            <td><strong><?= (int)$dp['student_count'] ?></strong></td>
                            <td><?= number_format($dp['avg_cgpa'] ?? 0, 2) ?></td>
                            <td><?= number_format($dp['avg_attendance'] ?? 0, 1) ?>%</td>
                            <td><?= number_format($dp['avg_academic'] ?? 0, 1) ?>%</td>
                            <td><?= number_format($dp['avg_technical'] ?? 0, 1) ?>%</td>
                            <td><?= number_format($dp['avg_career'] ?? 0, 1) ?>%</td>
                            <td><strong class="text-primary fs-6"><?= number_format($dp['avg_overall'] ?? 0, 1) ?>%</strong></td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle me-1"><?= (int)$dp['high_count'] ?> High</span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= (int)$dp['low_count'] ?> Support</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
