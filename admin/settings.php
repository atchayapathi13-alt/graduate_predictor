<?php
/**
 * Administrator Scoring Weights & System Settings
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Scoring Weights & System Configuration';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../classes/AnalysisEngine.php';
require_once __DIR__ . '/../classes/PredictionEngine.php';

requireRole('admin');
$db = getDB();

$error = '';
$success = '';

// Handle Reset to Defaults
if (isset($_POST['reset_defaults'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrf)) {
        $defaults = [
            'academic_weight'               => '40',
            'technical_weight'              => '25',
            'career_weight'                 => '20',
            'soft_skills_weight'            => '10',
            'extracurricular_weight'        => '5',
            'min_attendance_threshold'       => '75',
            'min_cgpa_threshold'             => '7.0',
            'high_readiness_threshold'       => '75',
            'moderate_readiness_threshold'   => '50'
        ];
        $stmtU = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        foreach ($defaults as $k => $v) {
            $stmtU->execute([$v, $k]);
        }

        // Recalculate all students
        $analysisEngine = new AnalysisEngine();
        $predEngine = new PredictionEngine();
        $students = $db->query("SELECT id FROM students")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($students as $sId) {
            $analysisEngine->calculateStudentAnalysis((int)$sId);
            $predEngine->predictStudentOutcome((int)$sId);
        }

        setFlash('success', 'Scoring weights successfully reset to default parameters, and all student scores refreshed!');
        header("Location: " . baseUrl('admin/settings.php'));
        exit;
    }
}

// Handle Form Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['reset_defaults'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security session expired. Please try again.';
    } else {
        $wAcademic = (float) ($_POST['academic_weight'] ?? 40);
        $wTech     = (float) ($_POST['technical_weight'] ?? 25);
        $wCareer   = (float) ($_POST['career_weight'] ?? 20);
        $wSoft     = (float) ($_POST['soft_skills_weight'] ?? 10);
        $wExtra    = (float) ($_POST['extracurricular_weight'] ?? 5);

        $attendanceThresh = (float) ($_POST['min_attendance_threshold'] ?? 75);
        $cgpaThresh       = (float) ($_POST['min_cgpa_threshold'] ?? 7.0);
        $highThresh       = (float) ($_POST['high_readiness_threshold'] ?? 75);
        $modThresh        = (float) ($_POST['moderate_readiness_threshold'] ?? 50);

        $totalWeight = $wAcademic + $wTech + $wCareer + $wSoft + $wExtra;

        if (abs($totalWeight - 100.0) > 0.01) {
            $error = "The sum of the 5 evaluation weights must equal exactly 100%. Current sum: {$totalWeight}%.";
        } elseif ($wAcademic < 0 || $wTech < 0 || $wCareer < 0 || $wSoft < 0 || $wExtra < 0) {
            $error = 'Weights cannot be negative values.';
        } else {
            $updates = [
                'academic_weight'               => (string)$wAcademic,
                'technical_weight'              => (string)$wTech,
                'career_weight'                 => (string)$wCareer,
                'soft_skills_weight'            => (string)$wSoft,
                'extracurricular_weight'        => (string)$wExtra,
                'min_attendance_threshold'       => (string)$attendanceThresh,
                'min_cgpa_threshold'             => (string)$cgpaThresh,
                'high_readiness_threshold'       => (string)$highThresh,
                'moderate_readiness_threshold'   => (string)$modThresh
            ];

            $stmtU = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            foreach ($updates as $k => $v) {
                $stmtU->execute([$v, $k]);
            }

            // Immediately recalculate performance analysis and predictions across all students
            $analysisEngine = new AnalysisEngine();
            $predEngine = new PredictionEngine();
            $students = $db->query("SELECT id FROM students")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($students as $sId) {
                $analysisEngine->calculateStudentAnalysis((int)$sId);
                $predEngine->predictStudentOutcome((int)$sId);
            }

            setFlash('success', "Configurable scoring weights updated and " . count($students) . " student profiles recalculated across the system!");
            header("Location: " . baseUrl('admin/settings.php'));
            exit;
        }
    }
}

$settings = getSettings();

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="content-card">
            <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0"><i class="fas fa-sliders me-2 text-primary"></i>Configurable Scoring Weights Engine</h5>
                    <small class="text-muted">Fine-tune institutional percentage weights and readiness threshold boundaries</small>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Total: 100% Target</span>
            </div>
            <div class="content-card-body p-4">

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small mb-4"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= baseUrl('admin/settings.php') ?>" id="weightsForm">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <!-- Weights Section -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Evaluation Domain Weights (Must sum to 100%)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Academic Performance (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="1" min="0" max="100" class="form-control weight-input" name="academic_weight" value="<?= $settings['academic_weight'] ?? 40 ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Default: 40% (CGPA, Attendance, Backlogs)</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Technical & Coding (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="1" min="0" max="100" class="form-control weight-input" name="technical_weight" value="<?= $settings['technical_weight'] ?? 25 ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Default: 25% (Skills, Projects, Certs)</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Career Readiness (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="1" min="0" max="100" class="form-control weight-input" name="career_weight" value="<?= $settings['career_weight'] ?? 20 ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Default: 20% (Internships, Repos)</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Soft Skills & Communication (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="1" min="0" max="100" class="form-control weight-input" name="soft_skills_weight" value="<?= $settings['soft_skills_weight'] ?? 10 ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Default: 10% (Presentation, Teamwork)</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Extracurricular Activities (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="1" min="0" max="100" class="form-control weight-input" name="extracurricular_weight" value="<?= $settings['extracurricular_weight'] ?? 5 ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Default: 5% (Hackathons, Clubs)</small>
                        </div>
                    </div>

                    <!-- Live Weight Sum Counter -->
                    <div class="p-3 bg-light rounded-3 border mb-4 d-flex justify-content-between align-items-center">
                        <span class="small fw-semibold text-dark">Current Sum of Configured Weights:</span>
                        <span id="weightSumBadge" class="badge bg-success rounded-pill px-3 py-2 fs-6">100%</span>
                    </div>

                    <!-- Benchmark Thresholds Section -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Institutional Screening Thresholds</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Minimum Attendance Benchmark (%)</label>
                            <input type="number" step="0.5" min="0" max="100" class="form-control" name="min_attendance_threshold" value="<?= $settings['min_attendance_threshold'] ?? 75 ?>" required>
                            <small class="text-muted">Threshold triggering shortage alerts (default: 75%)</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Minimum CGPA Benchmark (0 - 10)</label>
                            <input type="number" step="0.1" min="0" max="10" class="form-control" name="min_cgpa_threshold" value="<?= $settings['min_cgpa_threshold'] ?? 7.0 ?>" required>
                            <small class="text-muted">Screening benchmark for placement eligibility (default: 7.00)</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">High Readiness Score Cutoff (0 - 100)</label>
                            <input type="number" step="1" min="0" max="100" class="form-control" name="high_readiness_threshold" value="<?= $settings['high_readiness_threshold'] ?? 75 ?>" required>
                            <small class="text-muted">Overall score boundary for High Readiness classification</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Moderate Readiness Score Cutoff (0 - 100)</label>
                            <input type="number" step="1" min="0" max="100" class="form-control" name="moderate_readiness_threshold" value="<?= $settings['moderate_readiness_threshold'] ?? 50 ?>" required>
                            <small class="text-muted">Boundary below which students need remediation</small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <button type="submit" name="reset_defaults" value="1" class="btn btn-outline-secondary rounded-pill px-3" onclick="return confirm('Reset all weights to factory default standards (40, 25, 20, 10, 5)?');">
                            <i class="fas fa-rotate-left me-1"></i> Reset to Factory Defaults
                        </button>

                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" id="btnSaveWeights">
                            <i class="fas fa-floppy-disk me-1"></i> Save & Recalculate All Students
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputs = document.querySelectorAll('.weight-input');
    const sumBadge = document.getElementById('weightSumBadge');
    const saveBtn = document.getElementById('btnSaveWeights');

    function calculateTotal() {
        let total = 0;
        inputs.forEach(inp => {
            total += parseFloat(inp.value) || 0;
        });
        total = Math.round(total * 100) / 100;
        sumBadge.innerText = total + '%';

        if (Math.abs(total - 100) < 0.01) {
            sumBadge.className = 'badge bg-success rounded-pill px-3 py-2 fs-6';
            saveBtn.disabled = false;
        } else {
            sumBadge.className = 'badge bg-danger rounded-pill px-3 py-2 fs-6';
            saveBtn.disabled = true;
        }
    }

    inputs.forEach(inp => {
        inp.addEventListener('input', calculateTotal);
    });

    calculateTotal();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
