<?php
/**
 * Faculty / Analyst Dashboard
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Faculty Analytics Dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

requireRole(['faculty', 'admin']);
$currentUser = getCurrentUser();
$db = getDB();

// 1. Overall Aggregates
$totalStudents = (int) $db->query("SELECT count(*) FROM students")->fetchColumn();

// Readiness Counts
$stmtReadiness = $db->query("
    SELECT category, count(*) as count 
    FROM predictions 
    GROUP BY category
");
$readinessCounts = $stmtReadiness->fetchAll(PDO::FETCH_KEY_PAIR);
$highCount = (int) ($readinessCounts['High Readiness'] ?? 0);
$modCount  = (int) ($readinessCounts['Moderate Readiness'] ?? 0);
$lowCount  = (int) ($readinessCounts['Needs Improvement'] ?? 0);

// Averages
$stmtAverages = $db->query("
    SELECT 
        ROUND(AVG(ar.current_cgpa), 2) as avg_cgpa,
        ROUND(AVG(ar.attendance_percentage), 1) as avg_attendance,
        ROUND(AVG(pa.technical_score), 1) as avg_technical,
        ROUND(AVG(pa.overall_score), 1) as avg_overall
    FROM students s
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    LEFT JOIN performance_analysis pa ON s.id = pa.student_id
");
$avg = $stmtAverages->fetch();

// 2. Department Metrics
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
$deptLabels = array_column($deptMetrics, 'dept_code');
$deptCGPAs = array_map('floatval', array_column($deptMetrics, 'avg_cgpa'));
$deptAttendances = array_map('floatval', array_column($deptMetrics, 'avg_attendance'));
$deptOveralls = array_map('floatval', array_column($deptMetrics, 'avg_overall'));

// 3. CGPA Buckets
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

// 4. Recent Student Cohort
$stmtRecent = $db->query("
    SELECT s.id, s.student_id, u.name, d.dept_code,
           ar.current_cgpa, ar.attendance_percentage, ar.num_backlogs,
           pa.overall_score, pr.category as prediction_category
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    LEFT JOIN performance_analysis pa ON s.id = pa.student_id
    LEFT JOIN predictions pr ON s.id = pr.student_id
    ORDER BY s.id ASC LIMIT 6
");
$recentStudents = $stmtRecent->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Title & Quick Actions -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Faculty Academic & Placement Analytics</h4>
        <p class="text-muted small mb-0">Cohort intelligence, readiness distribution, and student mentoring indicators</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= baseUrl('faculty/comparison.php') ?>" class="btn btn-outline-primary rounded-pill px-3">
            <i class="fas fa-scale-balanced me-1"></i> Compare Cohorts
        </a>
        <a href="<?= baseUrl('faculty/students.php') ?>" class="btn btn-primary rounded-pill px-3 fw-semibold">
            <i class="fas fa-users me-1"></i> Student Directory
        </a>
    </div>
</div>

<!-- 7 Key Aggregates Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Total Students</span>
                <div class="stat-icon primary"><i class="fas fa-users"></i></div>
            </div>
            <h3 class="fw-bold mb-0 text-primary"><?= $totalStudents ?></h3>
            <small class="text-muted">Enrolled cohort</small>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">High Readiness</span>
                <div class="stat-icon success"><i class="fas fa-circle-check"></i></div>
            </div>
            <h3 class="fw-bold mb-0 text-success"><?= $highCount ?></h3>
            <small class="text-muted"><?= $totalStudents > 0 ? round(($highCount / $totalStudents) * 100) : 0 ?>% of cohort</small>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Moderate</span>
                <div class="stat-icon warning"><i class="fas fa-circle-exclamation"></i></div>
            </div>
            <h3 class="fw-bold mb-0 text-warning"><?= $modCount ?></h3>
            <small class="text-muted">Targeted coaching</small>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Need Support</span>
                <div class="stat-icon danger"><i class="fas fa-triangle-exclamation"></i></div>
            </div>
            <h3 class="fw-bold mb-0 text-danger"><?= $lowCount ?></h3>
            <small class="text-muted"><a href="<?= baseUrl('faculty/at_risk.php') ?>" class="text-danger fw-semibold">View at-risk &rarr;</a></small>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Avg CGPA</span>
                <div class="stat-icon cyan"><i class="fas fa-chart-line"></i></div>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= number_format($avg['avg_cgpa'] ?? 0, 2) ?></h3>
            <small class="text-muted">Target &ge; 7.00</small>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Avg Attendance</span>
                <div class="stat-icon purple"><i class="fas fa-clock"></i></div>
            </div>
            <h3 class="fw-bold mb-0 text-purple"><?= number_format($avg['avg_attendance'] ?? 0, 1) ?>%</h3>
            <small class="text-muted">Target &ge; 75%</small>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div class="row g-4 mb-4">
    <!-- Chart 1: Readiness Distribution Doughnut -->
    <div class="col-lg-5">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Cohort Readiness Distribution</h6>
            </div>
            <div class="content-card-body d-flex flex-column align-items-center justify-content-center">
                <div style="height: 250px; width: 100%; position: relative;">
                    <canvas id="facultyReadinessPie"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 2: Department-Level Performance Comparison -->
    <div class="col-lg-7">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-building-columns me-2 text-cyan"></i>Department Average Score vs Attendance</h6>
            </div>
            <div class="content-card-body">
                <div style="height: 260px; width: 100%;">
                    <canvas id="deptComparisonChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 3: CGPA Distribution Histogram -->
    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-chart-simple me-2 text-warning"></i>CGPA Distribution Spread</h6>
            </div>
            <div class="content-card-body">
                <div style="height: 250px; width: 100%;">
                    <canvas id="cgpaSpreadChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Action / Mentoring Card -->
    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="content-card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-danger"><i class="fas fa-shield-virus me-2"></i>Priority Mentoring Actions</h6>
                <a href="<?= baseUrl('faculty/at_risk.php') ?>" class="small text-danger text-decoration-none">Review All &rarr;</a>
            </div>
            <div class="content-card-body p-4">
                <div class="d-flex flex-column gap-3">
                    <div class="p-3 bg-danger bg-opacity-10 border border-danger-subtle rounded-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-danger small">Attendance Shortage Alert (< 75%)</strong>
                            <span class="badge bg-danger text-white rounded-pill">Urgent</span>
                        </div>
                        <p class="text-muted small mb-0">Students below 75% attendance risk university condonation disqualification for upcoming semester exams.</p>
                    </div>

                    <div class="p-3 bg-warning bg-opacity-10 border border-warning-subtle rounded-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-warning text-dark small">Active Backlog Remediation</strong>
                            <span class="badge bg-warning text-dark rounded-pill">Coaching</span>
                        </div>
                        <p class="text-muted small mb-0">Host faculty office hours to help students with backlogs prepare for supplementary exams before campus recruitment drives start.</p>
                    </div>

                    <div class="p-3 bg-primary bg-opacity-10 border border-primary-subtle rounded-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-primary small">Mandatory Project & Internship Drive</strong>
                            <span class="badge bg-primary text-white rounded-pill">Career</span>
                        </div>
                        <p class="text-muted small mb-0">Encourage Moderate Readiness students to complete at least one documented capstone project and industry certification.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Cohort Table Preview -->
<div class="content-card mb-4">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="fas fa-table-list me-2 text-primary"></i>Student Performance Overview</h6>
        <a href="<?= baseUrl('faculty/students.php') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
            View All Students &rarr;
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Dept</th>
                    <th>CGPA</th>
                    <th>Attendance</th>
                    <th>Backlogs</th>
                    <th>Overall Score</th>
                    <th>Prediction</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentStudents as $s): ?>
                    <tr>
                        <td><span class="font-monospace fw-bold text-dark"><?= sanitize($s['student_id']) ?></span></td>
                        <td><?= sanitize($s['name']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= sanitize($s['dept_code']) ?></span></td>
                        <td><strong><?= number_format($s['current_cgpa'], 2) ?></strong></td>
                        <td>
                            <span class="<?= $s['attendance_percentage'] < 75 ? 'text-danger fw-bold' : 'text-success' ?>">
                                <?= number_format($s['attendance_percentage'], 1) ?>%
                            </span>
                        </td>
                        <td>
                            <?= $s['num_backlogs'] > 0 ? '<span class="badge bg-danger rounded-pill">' . $s['num_backlogs'] . '</span>' : '<span class="text-muted">0</span>' ?>
                        </td>
                        <td><strong><?= number_format($s['overall_score'], 1) ?>%</strong></td>
                        <td><?= getReadinessBadge($s['prediction_category'] ?? 'Needs Improvement') ?></td>
                        <td>
                            <a href="<?= baseUrl('faculty/student_detail.php?id=' . $s['id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3 py-1">
                                <i class="fas fa-eye me-1"></i> View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Readiness Distribution Doughnut
    AppCharts.initReadinessPieChart('facultyReadinessPie', <?= json_encode($readinessCounts) ?>);

    // 2. Department Comparison Multi-Bar
    const ctxDept = document.getElementById('deptComparisonChart');
    if (ctxDept) {
        new Chart(ctxDept, {
            type: 'bar',
            data: {
                labels: <?= json_encode($deptLabels) ?>,
                datasets: [
                    {
                        label: 'Avg Overall Score (%)',
                        data: <?= json_encode($deptOveralls) ?>,
                        backgroundColor: 'rgba(79, 70, 229, 0.85)',
                        borderRadius: 6
                    },
                    {
                        label: 'Avg Attendance (%)',
                        data: <?= json_encode($deptAttendances) ?>,
                        backgroundColor: 'rgba(6, 182, 212, 0.85)',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100 }
                },
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // 3. CGPA Spread Histogram
    const ctxCgpa = document.getElementById('cgpaSpreadChart');
    if (ctxCgpa) {
        new Chart(ctxCgpa, {
            type: 'bar',
            data: {
                labels: ['9.0 - 10.0', '8.0 - 8.9', '7.0 - 7.9', '6.0 - 6.9', '< 6.0'],
                datasets: [{
                    label: 'Students Count',
                    data: [
                        <?= (int)($cgpaBuckets['9_10'] ?? 0) ?>,
                        <?= (int)($cgpaBuckets['8_8.9'] ?? 0) ?>,
                        <?= (int)($cgpaBuckets['7_7.9'] ?? 0) ?>,
                        <?= (int)($cgpaBuckets['6_6.9'] ?? 0) ?>,
                        <?= (int)($cgpaBuckets['below_6'] ?? 0) ?>
                    ],
                    backgroundColor: [
                        '#10b981', '#3b82f6', '#06b6d4', '#f59e0b', '#ef4444'
                    ],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
