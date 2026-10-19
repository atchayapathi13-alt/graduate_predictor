<?php
/**
 * Student Performance Analysis Breakdown
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Academic & Performance Analysis';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../classes/AnalysisEngine.php';

requireRole('student');
$currentUser = getCurrentUser();
$studentId = $currentUser['student_id'];

$analysisEngine = new AnalysisEngine();
if (isset($_GET['recalculate'])) {
    $analysisEngine->calculateStudentAnalysis($studentId);
    setFlash('success', 'Analysis scores recalculated successfully with the latest configuration.');
    header("Location: " . baseUrl('student/analysis.php'));
    exit;
}

$scores = $analysisEngine->calculateStudentAnalysis($studentId);

include __DIR__ . '/../includes/header.php';
?>

<!-- Title & Recalculate Action -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Detailed Scoring & Analytical Breakdown</h4>
        <p class="text-muted small mb-0">Transparent multi-criteria evaluation of your academic, technical, and career indicators</p>
    </div>
    <a href="<?= baseUrl('student/analysis.php?recalculate=1') ?>" class="btn btn-outline-primary rounded-pill px-3 fw-semibold">
        <i class="fas fa-arrows-rotate me-1"></i> Recalculate Now
    </a>
</div>

<!-- Overall Score Highlight Card -->
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="content-card h-100 text-center p-4 d-flex flex-column justify-content-center align-items-center">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-3 font-monospace">
                AGGREGATE PERFORMANCE INDEX
            </span>
            <div class="display-3 fw-bold text-dark mb-1"><?= number_format($scores['overall_score'], 1) ?></div>
            <div class="text-muted small mb-3">Out of 100 Maximum Points</div>
            <div class="w-100 px-3">
                <div class="progress progress-custom mb-2">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= min(100, $scores['overall_score']) ?>%"></div>
                </div>
                <div class="d-flex justify-content-between text-muted" style="font-size: 0.75rem;">
                    <span>0%</span>
                    <span>50% Threshold</span>
                    <span>100%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Scoring Weight Formula Card -->
    <div class="col-lg-8">
        <div class="content-card h-100 p-4">
            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-calculator me-2 text-primary"></i>Configurable Scoring Weights Formula</h6>
            <p class="text-muted small">The overall score is computed as a weighted average configured by institutional administration:</p>
            
            <div class="p-3 bg-light rounded-3 font-monospace small mb-3 border">
                Overall Score = (
                    (Academic &times; <?= $scores['weights']['academic'] ?>%) +
                    (Technical &times; <?= $scores['weights']['technical'] ?>%) +
                    (Career Readiness &times; <?= $scores['weights']['career'] ?>%) +
                    (Soft Skills &times; <?= $scores['weights']['soft_skills'] ?>%) +
                    (Extracurricular &times; <?= $scores['weights']['extracurricular'] ?>%)
                ) / 100
            </div>

            <div class="row g-2 text-center pt-2">
                <div class="col-sm">
                    <div class="border rounded-2 p-2 bg-white">
                        <small class="text-muted d-block">Academic</small>
                        <strong class="text-primary"><?= $scores['weights']['academic'] ?>%</strong>
                    </div>
                </div>
                <div class="col-sm">
                    <div class="border rounded-2 p-2 bg-white">
                        <small class="text-muted d-block">Technical</small>
                        <strong class="text-cyan"><?= $scores['weights']['technical'] ?>%</strong>
                    </div>
                </div>
                <div class="col-sm">
                    <div class="border rounded-2 p-2 bg-white">
                        <small class="text-muted d-block">Career Ready</small>
                        <strong class="text-success"><?= $scores['weights']['career'] ?>%</strong>
                    </div>
                </div>
                <div class="col-sm">
                    <div class="border rounded-2 p-2 bg-white">
                        <small class="text-muted d-block">Soft Skills</small>
                        <strong class="text-warning"><?= $scores['weights']['soft_skills'] ?>%</strong>
                    </div>
                </div>
                <div class="col-sm">
                    <div class="border rounded-2 p-2 bg-white">
                        <small class="text-muted d-block">Extracurricular</small>
                        <strong class="text-purple"><?= $scores['weights']['extracurricular'] ?>%</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5 Domain Scores Breakdown Table -->
<div class="content-card mb-4">
    <div class="content-card-header">
        <h6 class="fw-bold mb-0"><i class="fas fa-layer-group me-2 text-primary"></i>Domain-Wise Score Calculation Summary</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Evaluation Dimension</th>
                    <th>Weight</th>
                    <th>Attained Score</th>
                    <th>Weighted Contribution</th>
                    <th>Key Contributing Factors</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong class="text-dark">Academic Performance</strong>
                        <small class="text-muted d-block">Core curricular consistency</small>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= $scores['weights']['academic'] ?>%</span></td>
                    <td><span class="fw-bold text-primary"><?= number_format($scores['academic_score'], 1) ?>%</span></td>
                    <td><span class="fw-bold"><?= number_format(($scores['academic_score'] * $scores['weights']['academic']) / 100, 2) ?> pts</span></td>
                    <td class="small text-muted">
                        CGPA: <?= $scores['breakdown']['academic']['cgpa'] ?> | 
                        Attendance: <?= $scores['breakdown']['academic']['attendance'] ?>% | 
                        Backlogs: <?= $scores['breakdown']['academic']['backlogs'] ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong class="text-dark">Technical & Programming Skills</strong>
                        <small class="text-muted d-block">Hands-on coding capability</small>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= $scores['weights']['technical'] ?>%</span></td>
                    <td><span class="fw-bold text-cyan"><?= number_format($scores['technical_score'], 1) ?>%</span></td>
                    <td><span class="fw-bold"><?= number_format(($scores['technical_score'] * $scores['weights']['technical']) / 100, 2) ?> pts</span></td>
                    <td class="small text-muted">
                        <?= $scores['breakdown']['technical']['project_count'] ?> Projects | 
                        <?= $scores['breakdown']['technical']['certification_count'] ?> Certifications
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong class="text-dark">Career Readiness</strong>
                        <small class="text-muted d-block">Industry preparedness</small>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= $scores['weights']['career'] ?>%</span></td>
                    <td><span class="fw-bold text-success"><?= number_format($scores['career_readiness_score'], 1) ?>%</span></td>
                    <td><span class="fw-bold"><?= number_format(($scores['career_readiness_score'] * $scores['weights']['career']) / 100, 2) ?> pts</span></td>
                    <td class="small text-muted">
                        Internship: <?= $scores['breakdown']['career']['has_internship'] ? 'Completed' : 'Pending' ?> | 
                        GitHub Repositories
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong class="text-dark">Soft Skills & Aptitude</strong>
                        <small class="text-muted d-block">Interview & communication</small>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= $scores['weights']['soft_skills'] ?>%</span></td>
                    <td><span class="fw-bold text-warning"><?= number_format($scores['soft_skills_score'], 1) ?>%</span></td>
                    <td><span class="fw-bold"><?= number_format(($scores['soft_skills_score'] * $scores['weights']['soft_skills']) / 100, 2) ?> pts</span></td>
                    <td class="small text-muted">
                        Communication, Teamwork, Leadership & Problem Solving
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong class="text-dark">Extracurricular Activities</strong>
                        <small class="text-muted d-block">Co-curricular achievements</small>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= $scores['weights']['extracurricular'] ?>%</span></td>
                    <td><span class="fw-bold text-purple"><?= number_format($scores['extracurricular_score'], 1) ?>%</span></td>
                    <td><span class="fw-bold"><?= number_format(($scores['extracurricular_score'] * $scores['weights']['extracurricular']) / 100, 2) ?> pts</span></td>
                    <td class="small text-muted">
                        <?= $scores['breakdown']['extracurricular']['activity_count'] ?> Events (Hackathons, Competitions, Exhibitions)
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart Visualizer -->
<div class="content-card mb-4">
    <div class="content-card-header">
        <h6 class="fw-bold mb-0"><i class="fas fa-chart-column me-2 text-primary"></i>Comparative Domain Chart</h6>
    </div>
    <div class="content-card-body">
        <div style="height: 300px; width: 100%;">
            <canvas id="analysisBarChart"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    AppCharts.initDomainBarChart('analysisBarChart', {
        academic: <?= json_encode($scores['academic_score']) ?>,
        technical: <?= json_encode($scores['technical_score']) ?>,
        career: <?= json_encode($scores['career_readiness_score']) ?>,
        soft_skills: <?= json_encode($scores['soft_skills_score']) ?>,
        extracurricular: <?= json_encode($scores['extracurricular_score']) ?>
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
