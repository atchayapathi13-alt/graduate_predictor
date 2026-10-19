<?php
/**
 * Student Outcome Prediction & Readiness View
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Outcome Prediction';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../classes/AnalysisEngine.php';
require_once __DIR__ . '/../classes/PredictionEngine.php';

requireRole('student');
$currentUser = getCurrentUser();
$studentId = $currentUser['student_id'];

$analysisEngine = new AnalysisEngine();
$predEngine = new PredictionEngine();

$scores = $analysisEngine->calculateStudentAnalysis($studentId);
$prediction = $predEngine->predictStudentOutcome($studentId);

include __DIR__ . '/../includes/header.php';
?>

<!-- Headline Section -->
<div class="mb-4">
    <h4 class="fw-bold mb-1">Graduate Outcome Readiness Prediction</h4>
    <p class="text-muted small mb-0">Predicted based on the student's submitted academic and skill profile.</p>
</div>

<!-- Main Prediction Banner Card -->
<?php
    $cat = $prediction['category'];
    $bannerClass = ($cat === 'High Readiness') ? 'high' : (($cat === 'Moderate Readiness') ? 'moderate' : 'low');
?>
<div class="readiness-banner <?= $bannerClass ?> mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <span class="badge bg-white text-dark rounded-pill px-3 py-1 mb-2 font-monospace fw-bold">
                ESTIMATED OUTCOME CATEGORY
            </span>
            <h1 class="display-5 fw-extrabold mb-2"><?= strtoupper(sanitize($cat)) ?></h1>
            <p class="fs-6 opacity-90 mb-3"><?= sanitize($prediction['prediction_notes']) ?></p>
            <div class="d-inline-flex align-items-center gap-2 bg-black bg-opacity-25 rounded-pill px-3 py-1 text-light small">
                <i class="fas fa-check-circle"></i>
                <span>Evaluation Confidence / Profile Completeness Index: <strong><?= number_format($prediction['confidence_score'], 1) ?>%</strong></span>
            </div>
        </div>
        <div class="col-lg-4 text-lg-end">
            <div class="bg-white bg-opacity-20 p-4 rounded-4 backdrop-blur d-inline-block text-center w-100 max-w-300">
                <div class="small text-uppercase tracking-wider opacity-75 mb-1">Overall Performance Score</div>
                <div class="display-4 fw-bold mb-0"><?= number_format($scores['overall_score'], 1) ?></div>
                <div class="small opacity-90">Scale 0 - 100 Points</div>
            </div>
        </div>
    </div>
</div>

<!-- 5 Domain Attainment Summary Strip -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md">
        <div class="stat-card text-center p-3">
            <span class="text-muted small fw-semibold d-block">Academic</span>
            <h4 class="fw-bold mb-0 text-primary"><?= number_format($scores['academic_score'], 1) ?>%</h4>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card text-center p-3">
            <span class="text-muted small fw-semibold d-block">Technical</span>
            <h4 class="fw-bold mb-0 text-cyan"><?= number_format($scores['technical_score'], 1) ?>%</h4>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card text-center p-3">
            <span class="text-muted small fw-semibold d-block">Career Ready</span>
            <h4 class="fw-bold mb-0 text-success"><?= number_format($scores['career_readiness_score'], 1) ?>%</h4>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card text-center p-3">
            <span class="text-muted small fw-semibold d-block">Soft Skills</span>
            <h4 class="fw-bold mb-0 text-warning"><?= number_format($scores['soft_skills_score'], 1) ?>%</h4>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card text-center p-3">
            <span class="text-muted small fw-semibold d-block">Extracurricular</span>
            <h4 class="fw-bold mb-0 text-purple"><?= number_format($scores['extracurricular_score'], 1) ?>%</h4>
        </div>
    </div>
</div>

<!-- Strengths vs Areas Requiring Improvement -->
<div class="row g-4 mb-4">
    <!-- Strengths Column -->
    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="content-card-header bg-success bg-opacity-10 border-success-subtle">
                <h6 class="fw-bold mb-0 text-success"><i class="fas fa-circle-check me-2"></i>Profile Strengths</h6>
            </div>
            <div class="content-card-body p-4">
                <p class="text-muted small mb-3">Key competencies and verified milestones elevating your graduate readiness:</p>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($prediction['strengths'] as $s): ?>
                        <div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-light border border-success-subtle">
                            <div class="text-success fs-5 mt-1"><i class="fas fa-check-circle"></i></div>
                            <div>
                                <strong class="small text-dark d-block"><?= sanitize($s) ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Weaknesses / Improvement Areas Column -->
    <div class="col-lg-6">
        <div class="content-card h-100">
            <div class="content-card-header bg-warning bg-opacity-10 border-warning-subtle">
                <h6 class="fw-bold mb-0 text-warning text-dark"><i class="fas fa-triangle-exclamation me-2 text-warning"></i>Areas Requiring Improvement</h6>
            </div>
            <div class="content-card-body p-4">
                <p class="text-muted small mb-3">Targeted deficit areas where focused effort will yield significant outcome enhancement:</p>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($prediction['weaknesses'] as $w): ?>
                        <div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-light border border-warning-subtle">
                            <div class="text-warning fs-5 mt-1"><i class="fas fa-circle-exclamation"></i></div>
                            <div>
                                <strong class="small text-dark d-block"><?= sanitize($w) ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Next Steps & Disclaimer -->
<div class="content-card p-4 mb-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div>
            <h6 class="fw-bold mb-1"><i class="fas fa-compass me-2 text-primary"></i>Ready to upgrade your profile?</h6>
            <p class="text-muted small mb-0">Follow your personalized recommendations to transition toward the High Readiness tier.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= baseUrl('student/recommendations.php') ?>" class="btn btn-primary rounded-pill px-4 fw-semibold">
                <i class="fas fa-lightbulb me-1"></i> View Recommendations
            </a>
            <a href="<?= baseUrl('student/report.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="fas fa-print me-1"></i> Export Report
            </a>
        </div>
    </div>
</div>

<div class="disclaimer-box mb-4">
    <strong><i class="fas fa-shield-halved me-1"></i> Analytical Decision Support Disclaimer:</strong><br>
    <?= sanitize($prediction['disclaimer']) ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
