<?php
/**
 * Faculty Student Deep-Dive Analysis View
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Student Profile & Analysis';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../classes/AnalysisEngine.php';
require_once __DIR__ . '/../classes/PredictionEngine.php';
require_once __DIR__ . '/../classes/RecommendationEngine.php';

requireRole(['faculty', 'admin']);
$currentUser = getCurrentUser();
$db = getDB();

$studentId = (int) ($_GET['id'] ?? 0);
if (!$studentId) {
    header("Location: " . baseUrl('faculty/students.php'));
    exit;
}

// Ensure analysis and prediction are fresh
$analysisEngine = new AnalysisEngine();
$predEngine = new PredictionEngine();
$recEngine = new RecommendationEngine();

$scores = $analysisEngine->calculateStudentAnalysis($studentId);
$prediction = $predEngine->predictStudentOutcome($studentId);
$recommendations = $recEngine->generateStudentRecommendations($studentId);

// Fetch student full details
$stmt = $db->prepare("
    SELECT s.*, u.name, u.email, u.phone, d.dept_name, d.dept_code,
           ar.tenth_percentage, ar.twelfth_percentage, ar.diploma_percentage,
           ar.current_cgpa, ar.current_semester, ar.num_backlogs, ar.cleared_backlogs,
           ar.attendance_percentage,
           ar.sem1_gpa, ar.sem2_gpa, ar.sem3_gpa, ar.sem4_gpa, ar.sem5_gpa, ar.sem6_gpa
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    die("Student record not found.");
}

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

// Fetch Certifications
$stmtC = $db->prepare("SELECT * FROM certifications WHERE student_id = ?");
$stmtC->execute([$studentId]);
$certs = $stmtC->fetchAll();

// Fetch Internships
$stmtI = $db->prepare("SELECT * FROM internships WHERE student_id = ?");
$stmtI->execute([$studentId]);
$internships = $stmtI->fetchAll();

// Fetch Projects
$stmtP = $db->prepare("SELECT * FROM projects WHERE student_id = ?");
$stmtP->execute([$studentId]);
$projects = $stmtP->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Action Bar -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="<?= baseUrl('faculty/students.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Back to Student Directory
    </a>
    <div class="d-flex gap-2">
        <a href="<?= baseUrl('student/report.php?student_id=' . $studentId) ?>" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-file-pdf me-1"></i> Full Printable Report
        </a>
    </div>
</div>

<!-- Student Header Card -->
<div class="content-card mb-4 p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fs-2 fw-bold" style="width: 64px; height: 64px;">
                <?= strtoupper(substr($student['name'], 0, 1)) ?>
            </div>
            <div>
                <h4 class="fw-bold mb-1 text-dark"><?= sanitize($student['name']) ?></h4>
                <div class="text-muted small">
                    <span class="font-monospace fw-bold text-dark me-2"><?= sanitize($student['student_id']) ?></span> &bull; 
                    <span class="me-2"><?= sanitize($student['dept_name']) ?> (<?= sanitize($student['dept_code']) ?>)</span> &bull; 
                    <span><?= sanitize($student['degree']) ?> (<?= sanitize($student['academic_year']) ?>)</span>
                </div>
            </div>
        </div>
        <div>
            <?= getReadinessBadge($prediction['category']) ?>
        </div>
    </div>
</div>

<!-- 5 Domain KPI Strip -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md">
        <div class="stat-card text-center p-3">
            <span class="text-muted small fw-semibold d-block">Overall Index</span>
            <h3 class="fw-bold mb-0 text-primary"><?= number_format($scores['overall_score'], 1) ?>%</h3>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card text-center p-3">
            <span class="text-muted small fw-semibold d-block">Academic</span>
            <h3 class="fw-bold mb-0 text-cyan"><?= number_format($scores['academic_score'], 1) ?>%</h3>
            <small class="text-muted">CGPA <?= number_format($student['current_cgpa'], 2) ?></small>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card text-center p-3">
            <span class="text-muted small fw-semibold d-block">Technical</span>
            <h3 class="fw-bold mb-0 text-purple"><?= number_format($scores['technical_score'], 1) ?>%</h3>
            <small class="text-muted"><?= count($projects) ?> Projects</small>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card text-center p-3">
            <span class="text-muted small fw-semibold d-block">Career Ready</span>
            <h3 class="fw-bold mb-0 text-success"><?= number_format($scores['career_readiness_score'], 1) ?>%</h3>
            <small class="text-muted"><?= count($internships) ?> Internships</small>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card text-center p-3">
            <span class="text-muted small fw-semibold d-block">Attendance</span>
            <h3 class="fw-bold mb-0 <?= $student['attendance_percentage'] < 75 ? 'text-danger' : 'text-success' ?>">
                <?= number_format($student['attendance_percentage'], 1) ?>%
            </h3>
            <small class="text-muted"><?= $student['num_backlogs'] ?> Backlogs</small>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-spider me-2 text-primary"></i>Technical Skill Matrix</h6>
            </div>
            <div class="content-card-body">
                <div style="height: 280px; width: 100%;">
                    <canvas id="facultyDetailRadar"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-chart-line me-2 text-success"></i>Semester GPA Progression</h6>
            </div>
            <div class="content-card-body">
                <div style="height: 280px; width: 100%;">
                    <canvas id="facultyDetailLine"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Strengths & Improvement Areas -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="content-card h-100">
            <div class="content-card-header bg-success bg-opacity-10 border-success-subtle">
                <h6 class="fw-bold mb-0 text-success"><i class="fas fa-circle-check me-2"></i>Observed Strengths</h6>
            </div>
            <div class="content-card-body p-3">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($prediction['strengths'] as $s): ?>
                        <li class="py-1 small"><i class="fas fa-check text-success me-2"></i><?= sanitize($s) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="content-card h-100">
            <div class="content-card-header bg-warning bg-opacity-10 border-warning-subtle">
                <h6 class="fw-bold mb-0 text-warning text-dark"><i class="fas fa-triangle-exclamation text-warning me-2"></i>Areas Needing Mentoring Attention</h6>
            </div>
            <div class="content-card-body p-3">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($prediction['weaknesses'] as $w): ?>
                        <li class="py-1 small"><i class="fas fa-circle-exclamation text-warning me-2"></i><?= sanitize($w) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Recommendations List -->
<div class="content-card mb-4">
    <div class="content-card-header">
        <h6 class="fw-bold mb-0"><i class="fas fa-lightbulb me-2 text-warning"></i>Advisory Recommendations Roadmap</h6>
    </div>
    <div class="content-card-body p-3">
        <div class="row g-3">
            <?php foreach ($recommendations as $r): ?>
                <div class="col-md-6">
                    <div class="p-3 bg-light border rounded-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-dark small"><?= sanitize($r['title']) ?></strong>
                            <?= getPriorityBadge($r['priority']) ?>
                        </div>
                        <p class="text-muted small mb-0"><?= sanitize($r['description']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    AppCharts.initSkillsRadarChart('facultyDetailRadar', <?= json_encode($radarLabels) ?>, <?= json_encode($radarValues) ?>);
    AppCharts.initSemesterLineChart('facultyDetailLine', <?= json_encode($semLabels) ?>, <?= json_encode($semGPAs) ?>);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
