<?php
/**
 * Student Dashboard
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Student Dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../classes/AnalysisEngine.php';
require_once __DIR__ . '/../classes/PredictionEngine.php';

requireRole('student');
$currentUser = getCurrentUser();
$studentId = $currentUser['student_id'];

$db = getDB();

// Ensure analysis and prediction are fresh
$analysisEngine = new AnalysisEngine();
$predEngine = new PredictionEngine();
$scores = $analysisEngine->calculateStudentAnalysis($studentId);
$prediction = $predEngine->predictStudentOutcome($studentId);

// Fetch student full details
$stmt = $db->prepare("
    SELECT s.*, u.name, u.email, d.dept_name, d.dept_code,
           ar.current_cgpa, ar.attendance_percentage, ar.num_backlogs, ar.cleared_backlogs,
           ar.sem1_gpa, ar.sem2_gpa, ar.sem3_gpa, ar.sem4_gpa, ar.sem5_gpa, ar.sem6_gpa,
           (SELECT count(*) FROM projects WHERE student_id = s.id) as project_count,
           (SELECT count(*) FROM certifications WHERE student_id = s.id) as cert_count,
           (SELECT count(*) FROM internships WHERE student_id = s.id AND has_internship = 1) as internship_count
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

// Fetch skills for radar chart
$stmtSkills = $db->prepare("
    SELECT s.name, ss.rating 
    FROM student_skills ss
    JOIN skills s ON ss.skill_id = s.id
    WHERE ss.student_id = ? AND s.category IN ('programming', 'technical')
    ORDER BY s.id ASC LIMIT 10
");
$stmtSkills->execute([$studentId]);
$radarSkills = $stmtSkills->fetchAll();
$radarLabels = array_column($radarSkills, 'name');
$radarValues = array_map('floatval', array_column($radarSkills, 'rating'));

// Prepare Semester Trajectory
$semLabels = ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4', 'Sem 5', 'Sem 6'];
$semGPAs = [
    (float)($student['sem1_gpa'] ?? 0),
    (float)($student['sem2_gpa'] ?? 0),
    (float)($student['sem3_gpa'] ?? 0),
    (float)($student['sem4_gpa'] ?? 0),
    (float)($student['sem5_gpa'] ?? 0),
    (float)($student['sem6_gpa'] ?? 0),
];

// Fetch Top 3 Recommendations
$stmtRecs = $db->prepare("SELECT * FROM recommendations WHERE student_id = ? ORDER BY id ASC LIMIT 3");
$stmtRecs->execute([$studentId]);
$topRecs = $stmtRecs->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Readiness Banner -->
<div class="row mb-4">
    <div class="col-12">
        <?php
            $cat = $prediction['category'];
            $bannerClass = ($cat === 'High Readiness') ? 'high' : (($cat === 'Moderate Readiness') ? 'moderate' : 'low');
        ?>
        <div class="readiness-banner <?= $bannerClass ?> d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge bg-white text-dark rounded-pill px-3 py-1 mb-2 font-monospace fw-bold">OUTCOME PREDICTION</span>
                <h2 class="fw-bold mb-1"><?= strtoupper(sanitize($cat)) ?></h2>
                <p class="mb-0 opacity-90 max-w-700"><?= sanitize($prediction['prediction_notes']) ?></p>
            </div>
            <div class="text-md-end flex-shrink-0">
                <div class="fs-1 fw-extrabold"><?= number_format($scores['overall_score'], 1) ?><span class="fs-4 fw-normal">/100</span></div>
                <div class="small opacity-75">Confidence Index: <?= number_format($prediction['confidence_score'], 1) ?>%</div>
                <a href="<?= baseUrl('student/prediction.php') ?>" class="btn btn-sm btn-light rounded-pill px-3 mt-2 fw-semibold">
                    <i class="fas fa-arrow-right me-1"></i> Full Prediction View
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 9 KPI Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Overall</span>
                <div class="stat-icon primary"><i class="fas fa-bullseye"></i></div>
            </div>
            <h4 class="fw-bold mb-0 text-primary"><?= number_format($scores['overall_score'], 1) ?>%</h4>
            <small class="text-muted">Weighted index</small>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Academic</span>
                <div class="stat-icon cyan"><i class="fas fa-graduation-cap"></i></div>
            </div>
            <h4 class="fw-bold mb-0 text-cyan"><?= number_format($scores['academic_score'], 1) ?>%</h4>
            <small class="text-muted">CGPA & Tests</small>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Technical</span>
                <div class="stat-icon purple"><i class="fas fa-code"></i></div>
            </div>
            <h4 class="fw-bold mb-0 text-purple"><?= number_format($scores['technical_score'], 1) ?>%</h4>
            <small class="text-muted">Skills & Projects</small>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Career Ready</span>
                <div class="stat-icon success"><i class="fas fa-briefcase"></i></div>
            </div>
            <h4 class="fw-bold mb-0 text-success"><?= number_format($scores['career_readiness_score'], 1) ?>%</h4>
            <small class="text-muted">Internship & Soft</small>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">Attendance</span>
                <div class="stat-icon <?= ($student['attendance_percentage'] >= 75) ? 'success' : 'danger' ?>"><i class="fas fa-clock"></i></div>
            </div>
            <h4 class="fw-bold mb-0 <?= ($student['attendance_percentage'] >= 75) ? 'text-success' : 'text-danger' ?>"><?= number_format($student['attendance_percentage'], 1) ?>%</h4>
            <small class="text-muted">Benchmark: 75%</small>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">CGPA</span>
                <div class="stat-icon warning"><i class="fas fa-chart-line"></i></div>
            </div>
            <h4 class="fw-bold mb-0 text-warning"><?= number_format($student['current_cgpa'], 2) ?></h4>
            <small class="text-muted"><?= $student['num_backlogs'] ?> Backlogs</small>
        </div>
    </div>
</div>

<!-- Secondary Stats Row (Certifications, Projects, Internship) -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold d-block">Certifications</span>
                <h4 class="fw-bold mb-0"><?= $student['cert_count'] ?> Verified</h4>
                <a href="<?= baseUrl('student/certifications.php') ?>" class="small text-primary text-decoration-none">Manage credentials &rarr;</a>
            </div>
            <div class="stat-icon primary"><i class="fas fa-certificate"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold d-block">Hands-on Projects</span>
                <h4 class="fw-bold mb-0"><?= $student['project_count'] ?> Projects</h4>
                <a href="<?= baseUrl('student/projects.php') ?>" class="small text-primary text-decoration-none">View portfolio &rarr;</a>
            </div>
            <div class="stat-icon purple"><i class="fas fa-diagram-project"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold d-block">Internship Status</span>
                <h4 class="fw-bold mb-0 <?= $student['internship_count'] > 0 ? 'text-success' : 'text-muted' ?>">
                    <?= $student['internship_count'] > 0 ? 'Completed' : 'Not Completed' ?>
                </h4>
                <a href="<?= baseUrl('student/internships.php') ?>" class="small text-primary text-decoration-none">Update internship &rarr;</a>
            </div>
            <div class="stat-icon success"><i class="fas fa-building-user"></i></div>
        </div>
    </div>
</div>

<!-- 5 Charts Grid -->
<div class="row g-4 mb-4">
    <!-- Chart 1: Overall Performance Doughnut -->
    <div class="col-lg-4">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Readiness Meter</h6>
                <span class="badge bg-light text-dark font-monospace"><?= number_format($scores['overall_score'], 1) ?>%</span>
            </div>
            <div class="content-card-body d-flex flex-column align-items-center justify-content-center">
                <div style="height: 220px; width: 100%; position: relative;">
                    <canvas id="overallDoughnutChart"></canvas>
                </div>
                <div class="text-center mt-3">
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1">
                        Category: <?= sanitize($prediction['category']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 2: Domain Breakdown Bar Chart -->
    <div class="col-lg-8">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-chart-column me-2 text-cyan"></i>Domain Performance Breakdown</h6>
                <a href="<?= baseUrl('student/analysis.php') ?>" class="small text-decoration-none">Details &rarr;</a>
            </div>
            <div class="content-card-body">
                <div style="height: 250px; width: 100%;">
                    <canvas id="domainBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 3: Technical Skills Radar -->
    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-spider me-2 text-purple"></i>Technical Skills Matrix</h6>
                <a href="<?= baseUrl('student/skills.php') ?>" class="small text-decoration-none">Edit Skills &rarr;</a>
            </div>
            <div class="content-card-body">
                <div style="height: 280px; width: 100%;">
                    <canvas id="skillsRadarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 4: Semester Performance Line Chart -->
    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-chart-line me-2 text-success"></i>Semester GPA Progression</h6>
                <a href="<?= baseUrl('student/academic.php') ?>" class="small text-decoration-none">Edit GPA &rarr;</a>
            </div>
            <div class="content-card-body">
                <div style="height: 280px; width: 100%;">
                    <canvas id="semesterLineChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Strengths & Recommendations Quick Summary Row -->
<div class="row g-4 mb-4">
    <!-- Strengths -->
    <div class="col-md-6">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0 text-success"><i class="fas fa-circle-check me-2"></i>Identified Profile Strengths</h6>
            </div>
            <div class="content-card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($prediction['strengths'] as $str): ?>
                        <li class="list-group-item d-flex align-items-center gap-2 px-0 py-2 border-0">
                            <i class="fas fa-check text-success flex-shrink-0"></i>
                            <span class="small"><?= sanitize($str) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Actionable Recommendations Preview -->
    <div class="col-md-6">
        <div class="content-card h-100">
            <div class="content-card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-warning"><i class="fas fa-lightbulb me-2"></i>Targeted Recommendations</h6>
                <a href="<?= baseUrl('student/recommendations.php') ?>" class="small text-decoration-none">View All &rarr;</a>
            </div>
            <div class="content-card-body">
                <?php if (empty($topRecs)): ?>
                    <p class="text-muted small">No specific improvement areas flagged. Keep up the high standard!</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($topRecs as $rec): ?>
                            <div class="p-3 rounded-3 bg-light border border-light-subtle">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="small text-dark"><?= sanitize($rec['title']) ?></strong>
                                    <?= getPriorityBadge($rec['priority']) ?>
                                </div>
                                <p class="text-muted small mb-0"><?= sanitize($rec['description']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Disclaimer Callout -->
<div class="disclaimer-box mb-4">
    <strong><i class="fas fa-info-circle me-1"></i> Academic Note:</strong>
    <?= sanitize($prediction['disclaimer']) ?>
</div>

<!-- Chart Initialization Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Overall Doughnut
    AppCharts.initOverallScoreDoughnut('overallDoughnutChart', <?= json_encode($scores['overall_score']) ?>);

    // 2. Domain Bar Chart
    AppCharts.initDomainBarChart('domainBarChart', {
        academic: <?= json_encode($scores['academic_score']) ?>,
        technical: <?= json_encode($scores['technical_score']) ?>,
        career: <?= json_encode($scores['career_readiness_score']) ?>,
        soft_skills: <?= json_encode($scores['soft_skills_score']) ?>,
        extracurricular: <?= json_encode($scores['extracurricular_score']) ?>
    });

    // 3. Technical Skills Radar
    AppCharts.initSkillsRadarChart(
        'skillsRadarChart',
        <?= json_encode($radarLabels) ?>,
        <?= json_encode($radarValues) ?>
    );

    // 4. Semester Progression Line
    AppCharts.initSemesterLineChart(
        'semesterLineChart',
        <?= json_encode($semLabels) ?>,
        <?= json_encode($semGPAs) ?>
    );
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
