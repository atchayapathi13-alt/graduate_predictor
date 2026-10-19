<?php
/**
 * Administrator Dashboard
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Administrator Dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

requireRole('admin');
$currentUser = getCurrentUser();
$db = getDB();

// 1. System Aggregates
$totalStudents = (int) $db->query("SELECT count(*) FROM students")->fetchColumn();
$totalFaculty  = (int) $db->query("SELECT count(*) FROM faculty")->fetchColumn();
$totalDepts    = (int) $db->query("SELECT count(*) FROM departments")->fetchColumn();

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

$highPct = $totalStudents > 0 ? round(($highCount / $totalStudents) * 100, 1) : 0;
$modPct  = $totalStudents > 0 ? round(($modCount / $totalStudents) * 100, 1) : 0;
$lowPct  = $totalStudents > 0 ? round(($lowCount / $totalStudents) * 100, 1) : 0;

// System Average Overall Score
$avgScore = (float) $db->query("SELECT ROUND(AVG(overall_score), 1) FROM performance_analysis")->fetchColumn();

// Department Performance
$stmtDept = $db->query("
    SELECT d.dept_code, d.dept_name,
           count(s.id) as student_count,
           ROUND(AVG(pa.overall_score), 1) as avg_overall
    FROM departments d
    LEFT JOIN students s ON d.id = s.department_id
    LEFT JOIN performance_analysis pa ON s.id = pa.student_id
    GROUP BY d.id
    ORDER BY d.id ASC
");
$deptMetrics = $stmtDept->fetchAll();
$deptLabels = array_column($deptMetrics, 'dept_code');
$deptOveralls = array_map('floatval', array_column($deptMetrics, 'avg_overall'));

// Recent Students
$stmtRecent = $db->query("
    SELECT s.id, s.student_id, u.name, d.dept_code,
           ar.current_cgpa, pa.overall_score, pr.category as prediction_category
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    LEFT JOIN performance_analysis pa ON s.id = pa.student_id
    LEFT JOIN predictions pr ON s.id = pr.student_id
    ORDER BY s.id DESC LIMIT 5
");
$recentStudents = $stmtRecent->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Title & Admin Actions -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Central Administration Control Center</h4>
        <p class="text-muted small mb-0">System configuration, institutional performance monitoring, and master entity management</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= baseUrl('admin/settings.php') ?>" class="btn btn-outline-primary rounded-pill px-3">
            <i class="fas fa-sliders me-1"></i> Scoring Weights
        </a>
        <a href="<?= baseUrl('faculty/export.php') ?>" class="btn btn-success rounded-pill px-3 fw-semibold">
            <i class="fas fa-file-export me-1"></i> Export Master Data
        </a>
    </div>
</div>

<!-- 7 Main KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Total Students</span>
                <div class="stat-icon primary"><i class="fas fa-user-graduate"></i></div>
            </div>
            <h3 class="fw-bold mb-0 text-primary"><?= $totalStudents ?></h3>
            <small class="text-muted"><a href="<?= baseUrl('admin/students.php') ?>" class="text-decoration-none">Manage &rarr;</a></small>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Total Faculty</span>
                <div class="stat-icon purple"><i class="fas fa-chalkboard-user"></i></div>
            </div>
            <h3 class="fw-bold mb-0 text-purple"><?= $totalFaculty ?></h3>
            <small class="text-muted"><a href="<?= baseUrl('admin/faculty.php') ?>" class="text-decoration-none">Manage &rarr;</a></small>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Departments</span>
                <div class="stat-icon cyan"><i class="fas fa-building-columns"></i></div>
            </div>
            <h3 class="fw-bold mb-0 text-cyan"><?= $totalDepts ?></h3>
            <small class="text-muted"><a href="<?= baseUrl('admin/departments.php') ?>" class="text-decoration-none">Manage &rarr;</a></small>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Avg Student Score</span>
                <div class="stat-icon warning"><i class="fas fa-chart-line"></i></div>
            </div>
            <h3 class="fw-bold mb-0 text-warning"><?= number_format($avgScore, 1) ?>%</h3>
            <small class="text-muted">Institutional index</small>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">High Readiness</span>
                <div class="stat-icon success"><i class="fas fa-circle-check"></i></div>
            </div>
            <h3 class="fw-bold mb-0 text-success"><?= $highPct ?>%</h3>
            <small class="text-muted"><?= $highCount ?> students</small>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Moderate</span>
                <div class="stat-icon warning"><i class="fas fa-circle-exclamation"></i></div>
            </div>
            <h3 class="fw-bold mb-0 text-warning"><?= $modPct ?>%</h3>
            <small class="text-muted"><?= $modCount ?> students</small>
        </div>
    </div>

    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Need Support</span>
                <div class="stat-icon danger"><i class="fas fa-triangle-exclamation"></i></div>
            </div>
            <h3 class="fw-bold mb-0 text-danger"><?= $lowPct ?>%</h3>
            <small class="text-muted"><?= $lowCount ?> students</small>
        </div>
    </div>
</div>

<!-- Admin Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>System Readiness Breakdown</h6>
            </div>
            <div class="content-card-body d-flex flex-column align-items-center justify-content-center">
                <div style="height: 250px; width: 100%;">
                    <canvas id="adminReadinessDoughnut"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-building-columns me-2 text-cyan"></i>Department Average Performance Index</h6>
            </div>
            <div class="content-card-body">
                <div style="height: 250px; width: 100%;">
                    <canvas id="adminDeptBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Quick Jump Modules Row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="p-4 bg-white rounded-4 border shadow-sm h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="stat-icon primary mb-3"><i class="fas fa-user-graduate"></i></div>
                <h6 class="fw-bold text-dark">Student Management</h6>
                <p class="text-muted small mb-0">Create new student records, modify personal data, or delete accounts.</p>
            </div>
            <a href="<?= baseUrl('admin/students.php') ?>" class="btn btn-sm btn-outline-primary rounded-pill mt-3">Access Module &rarr;</a>
        </div>
    </div>

    <div class="col-md-3">
        <div class="p-4 bg-white rounded-4 border shadow-sm h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="stat-icon purple mb-3"><i class="fas fa-chalkboard-user"></i></div>
                <h6 class="fw-bold text-dark">Faculty Management</h6>
                <p class="text-muted small mb-0">Assign department mentors, professors, and advisors.</p>
            </div>
            <a href="<?= baseUrl('admin/faculty.php') ?>" class="btn btn-sm btn-outline-purple rounded-pill mt-3">Access Module &rarr;</a>
        </div>
    </div>

    <div class="col-md-3">
        <div class="p-4 bg-white rounded-4 border shadow-sm h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="stat-icon cyan mb-3"><i class="fas fa-building-columns"></i></div>
                <h6 class="fw-bold text-dark">Departments</h6>
                <p class="text-muted small mb-0">Configure academic faculties, degree courses, and HOD assignments.</p>
            </div>
            <a href="<?= baseUrl('admin/departments.php') ?>" class="btn btn-sm btn-outline-cyan rounded-pill mt-3">Access Module &rarr;</a>
        </div>
    </div>

    <div class="col-md-3">
        <div class="p-4 bg-white rounded-4 border shadow-sm h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="stat-icon warning mb-3"><i class="fas fa-sliders"></i></div>
                <h6 class="fw-bold text-dark">Scoring Weights Engine</h6>
                <p class="text-muted small mb-0">Tune institutional percentage weights for academic, technical, and career scores.</p>
            </div>
            <a href="<?= baseUrl('admin/settings.php') ?>" class="btn btn-sm btn-outline-warning rounded-pill mt-3">Configure Weights &rarr;</a>
        </div>
    </div>
</div>

<!-- Recently Registered Students -->
<div class="content-card mb-4">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="fas fa-clock me-2 text-primary"></i>Recently Registered / Analyzed Students</h6>
        <a href="<?= baseUrl('admin/students.php') ?>" class="small text-decoration-none">Manage All Students &rarr;</a>
    </div>
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Dept</th>
                    <th>CGPA</th>
                    <th>Overall Score</th>
                    <th>Outcome Tier</th>
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
                        <td><strong><?= number_format($s['overall_score'], 1) ?>%</strong></td>
                        <td><?= getReadinessBadge($s['prediction_category'] ?? 'Needs Improvement') ?></td>
                        <td>
                            <a href="<?= baseUrl('faculty/student_detail.php?id=' . $s['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                Inspect
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
    // 1. Readiness Doughnut
    AppCharts.initReadinessPieChart('adminReadinessDoughnut', <?= json_encode($readinessCounts) ?>);

    // 2. Department Average Overall Score Bar Chart
    const ctxDept = document.getElementById('adminDeptBarChart');
    if (ctxDept) {
        new Chart(ctxDept, {
            type: 'bar',
            data: {
                labels: <?= json_encode($deptLabels) ?>,
                datasets: [{
                    label: 'Department Avg Performance Index (%)',
                    data: <?= json_encode($deptOveralls) ?>,
                    backgroundColor: 'rgba(79, 70, 229, 0.85)',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100 }
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
