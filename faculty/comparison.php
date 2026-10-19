<?php
/**
 * Multi-Student Analytical Comparison
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Student Profile Comparison';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

requireRole(['faculty', 'admin']);
$currentUser = getCurrentUser();
$db = getDB();

// Fetch all available students for selection dropdown
$allStudents = $db->query("
    SELECT s.id, s.student_id, u.name, d.dept_code 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    LEFT JOIN departments d ON s.department_id = d.id 
    ORDER BY s.id ASC
")->fetchAll();

// Determine selected students (defaults to 1, 3, and 4 if none provided)
$selectedIds = $_GET['students'] ?? [1, 3, 4];
if (!is_array($selectedIds)) {
    $selectedIds = explode(',', $selectedIds);
}
$selectedIds = array_slice(array_unique(array_filter(array_map('intval', $selectedIds))), 0, 5);

if (count($selectedIds) < 2 && count($allStudents) >= 2) {
    $selectedIds = [1, 2];
}

$comparedStudents = [];
if (!empty($selectedIds)) {
    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
    $stmt = $db->prepare("
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
    ");
    $stmt->execute($selectedIds);
    $comparedStudents = $stmt->fetchAll();
}

// Prepare datasets for Radar Chart
$radarDimensions = ['Academic', 'Technical', 'Career Ready', 'Soft Skills', 'Extracurricular', 'Attendance'];
$radarDatasets = [];
$barLabels = [];
$barOveralls = [];
$barCGPAs = [];
$barTechs = [];

foreach ($comparedStudents as $cs) {
    $radarDatasets[] = [
        'name' => $cs['name'] . ' (' . $cs['dept_code'] . ')',
        'values' => [
            (float) $cs['academic_score'],
            (float) $cs['technical_score'],
            (float) $cs['career_readiness_score'],
            (float) $cs['soft_skills_score'],
            (float) $cs['extracurricular_score'],
            (float) $cs['attendance_percentage']
        ]
    ];
    $barLabels[] = $cs['name'];
    $barOveralls[] = (float) $cs['overall_score'];
    $barCGPAs[] = (float) $cs['current_cgpa'] * 10; // Scaled to 100 for comparison bar
    $barTechs[] = (float) $cs['technical_score'];
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Title & Selector Header -->
<div class="mb-4">
    <h4 class="fw-bold mb-1">Comparative Student Analytics</h4>
    <p class="text-muted small mb-0">Multi-student analytical benchmarking across academic, technical, and industry readiness dimensions</p>
</div>

<!-- Student Multi-Selector Card -->
<div class="content-card mb-4">
    <div class="content-card-header bg-light">
        <h6 class="fw-bold mb-0"><i class="fas fa-users-viewfinder me-2 text-primary"></i>Select Cohort Members for Analytical Comparison (2 to 4 Students)</h6>
    </div>
    <div class="content-card-body p-4">
        <form method="GET" action="<?= baseUrl('faculty/comparison.php') ?>">
            <div class="row g-2 mb-3">
                <?php foreach ($allStudents as $st): ?>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="form-check p-2 bg-light rounded border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="students[]" value="<?= $st['id'] ?>" id="st_<?= $st['id'] ?>" <?= in_array($st['id'], $selectedIds) ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-semibold text-dark text-truncate" for="st_<?= $st['id'] ?>">
                                <?= sanitize($st['name']) ?> <span class="badge bg-secondary-subtle text-secondary"><?= sanitize($st['dept_code']) ?></span>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                    <i class="fas fa-scale-balanced me-1"></i> Update Comparison Visuals
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 2 Comparative Charts -->
<div class="row g-4 mb-4">
    <!-- Comparative Radar Chart -->
    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-spider me-2 text-primary"></i>Multi-Dimensional Performance Radar</h6>
            </div>
            <div class="content-card-body">
                <div style="height: 350px; width: 100%;">
                    <canvas id="comparisonRadarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparative Grouped Bar Chart -->
    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-chart-column me-2 text-cyan"></i>Overall Score & Technical Comparison</h6>
            </div>
            <div class="content-card-body">
                <div style="height: 350px; width: 100%;">
                    <canvas id="comparisonBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Comparative Matrix Table -->
<div class="content-card mb-4">
    <div class="content-card-header">
        <h6 class="fw-bold mb-0"><i class="fas fa-table-columns me-2 text-primary"></i>Comparative Indicators Matrix</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-custom table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    <th>Evaluation Indicator</th>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <th class="text-center">
                            <div class="fw-bold text-dark"><?= sanitize($cs['name']) ?></div>
                            <span class="badge bg-light text-dark border font-monospace"><?= sanitize($cs['student_id']) ?></span>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Department</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center"><span class="badge bg-secondary-subtle text-secondary"><?= sanitize($cs['dept_code']) ?></span></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Overall Performance Score</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center fw-bold fs-6 text-primary"><?= number_format($cs['overall_score'], 1) ?>/100</td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Estimated Outcome Category</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center"><?= getReadinessBadge($cs['prediction_category'] ?? 'Needs Improvement') ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Cumulative CGPA</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center fw-semibold"><?= number_format($cs['current_cgpa'], 2) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Attendance Percentage</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center <?= $cs['attendance_percentage'] < 75 ? 'text-danger fw-bold' : 'text-success' ?>">
                            <?= number_format($cs['attendance_percentage'], 1) ?>%
                        </td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Academic Score</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center"><?= number_format($cs['academic_score'], 1) ?>%</td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Technical Skill Score</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center"><?= number_format($cs['technical_score'], 1) ?>%</td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Career Readiness Score</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center"><?= number_format($cs['career_readiness_score'], 1) ?>%</td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Soft Skills Rating</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center"><?= number_format($cs['soft_skills_score'], 1) ?>%</td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Active Backlogs</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center"><?= $cs['num_backlogs'] > 0 ? '<span class="badge bg-danger">' . $cs['num_backlogs'] . '</span>' : '<span class="text-muted">0</span>' ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Projects Portfolio Count</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center"><?= $cs['project_count'] ?> Projects</td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Verified Certifications</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center"><?= $cs['cert_count'] ?> Certs</td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Internship Experience</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center"><?= $cs['internship_count'] > 0 ? '<span class="text-success"><i class="fas fa-check-circle"></i> Yes</span>' : '<span class="text-muted"><i class="fas fa-circle-xmark"></i> No</span>' ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <td><strong>Individual Report</strong></td>
                    <?php foreach ($comparedStudents as $cs): ?>
                        <td class="text-center">
                            <a href="<?= baseUrl('student/report.php?student_id=' . $cs['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                View Report &rarr;
                            </a>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="disclaimer-box mb-4">
    <strong><i class="fas fa-scale-balanced me-1"></i> Ethical Comparison Principle:</strong>
    This comparison is designed strictly for academic decision-support, cohort strength visualization, and personalized mentoring. It is not intended to rank individuals or imply personal worth.
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Comparison Radar Chart
    AppCharts.initComparisonRadar(
        'comparisonRadarChart',
        <?= json_encode($radarDimensions) ?>,
        <?= json_encode($radarDatasets) ?>
    );

    // 2. Comparison Bar Chart
    const ctxBar = document.getElementById('comparisonBarChart');
    if (ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?= json_encode($barLabels) ?>,
                datasets: [
                    {
                        label: 'Overall Performance Index (%)',
                        data: <?= json_encode($barOveralls) ?>,
                        backgroundColor: 'rgba(79, 70, 229, 0.85)',
                        borderRadius: 6
                    },
                    {
                        label: 'Technical Skill Score (%)',
                        data: <?= json_encode($barTechs) ?>,
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
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
