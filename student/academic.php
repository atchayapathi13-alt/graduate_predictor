<?php
/**
 * Student Academic Information Management
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Academic Information';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../classes/AnalysisEngine.php';
require_once __DIR__ . '/../classes/PredictionEngine.php';
require_once __DIR__ . '/../classes/RecommendationEngine.php';

requireRole('student');
$currentUser = getCurrentUser();
$studentId = $currentUser['student_id'];
$db = getDB();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security session expired. Please refresh.';
    } else {
        $tenth       = (float) ($_POST['tenth_percentage'] ?? 0);
        $twelfth     = !empty($_POST['twelfth_percentage']) ? (float)$_POST['twelfth_percentage'] : null;
        $diploma     = !empty($_POST['diploma_percentage']) ? (float)$_POST['diploma_percentage'] : null;
        $cgpa        = (float) ($_POST['current_cgpa'] ?? 0);
        $semester    = (int) ($_POST['current_semester'] ?? 1);
        $backlogs    = (int) ($_POST['num_backlogs'] ?? 0);
        $cleared     = (int) ($_POST['cleared_backlogs'] ?? 0);
        $attendance  = (float) ($_POST['attendance_percentage'] ?? 0);

        $sem1 = !empty($_POST['sem1_gpa']) ? (float)$_POST['sem1_gpa'] : null;
        $sem2 = !empty($_POST['sem2_gpa']) ? (float)$_POST['sem2_gpa'] : null;
        $sem3 = !empty($_POST['sem3_gpa']) ? (float)$_POST['sem3_gpa'] : null;
        $sem4 = !empty($_POST['sem4_gpa']) ? (float)$_POST['sem4_gpa'] : null;
        $sem5 = !empty($_POST['sem5_gpa']) ? (float)$_POST['sem5_gpa'] : null;
        $sem6 = !empty($_POST['sem6_gpa']) ? (float)$_POST['sem6_gpa'] : null;
        $sem7 = !empty($_POST['sem7_gpa']) ? (float)$_POST['sem7_gpa'] : null;
        $sem8 = !empty($_POST['sem8_gpa']) ? (float)$_POST['sem8_gpa'] : null;

        // Validation
        if ($cgpa < 0 || $cgpa > 10.0) {
            $error = 'Current CGPA must be between 0.00 and 10.00.';
        } elseif ($attendance < 0 || $attendance > 100.0) {
            $error = 'Attendance percentage must be between 0% and 100%.';
        } elseif ($tenth < 0 || $tenth > 100.0) {
            $error = '10th percentage must be between 0% and 100%.';
        } elseif ($backlogs < 0) {
            $error = 'Number of backlogs cannot be negative.';
        } else {
            // Update or Insert into academic_records
            $stmt = $db->prepare("
                INSERT INTO academic_records (
                    student_id, tenth_percentage, twelfth_percentage, diploma_percentage,
                    current_cgpa, current_semester, num_backlogs, cleared_backlogs,
                    attendance_percentage, sem1_gpa, sem2_gpa, sem3_gpa, sem4_gpa,
                    sem5_gpa, sem6_gpa, sem7_gpa, sem8_gpa, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                )
                ON DUPLICATE KEY UPDATE
                    tenth_percentage = VALUES(tenth_percentage),
                    twelfth_percentage = VALUES(twelfth_percentage),
                    diploma_percentage = VALUES(diploma_percentage),
                    current_cgpa = VALUES(current_cgpa),
                    current_semester = VALUES(current_semester),
                    num_backlogs = VALUES(num_backlogs),
                    cleared_backlogs = VALUES(cleared_backlogs),
                    attendance_percentage = VALUES(attendance_percentage),
                    sem1_gpa = VALUES(sem1_gpa),
                    sem2_gpa = VALUES(sem2_gpa),
                    sem3_gpa = VALUES(sem3_gpa),
                    sem4_gpa = VALUES(sem4_gpa),
                    sem5_gpa = VALUES(sem5_gpa),
                    sem6_gpa = VALUES(sem6_gpa),
                    sem7_gpa = VALUES(sem7_gpa),
                    sem8_gpa = VALUES(sem8_gpa),
                    updated_at = NOW()
            ");

            $stmt->execute([
                $studentId, $tenth, $twelfth, $diploma,
                $cgpa, $semester, $backlogs, $cleared,
                $attendance, $sem1, $sem2, $sem3, $sem4,
                $sem5, $sem6, $sem7, $sem8
            ]);

            // Automatically recalculate scores, prediction, and recommendations
            $analysisEngine = new AnalysisEngine();
            $predEngine = new PredictionEngine();
            $recEngine = new RecommendationEngine();

            $analysisEngine->calculateStudentAnalysis($studentId);
            $predEngine->predictStudentOutcome($studentId);
            $recEngine->generateStudentRecommendations($studentId);

            setFlash('success', 'Academic records updated and predictive analysis refreshed successfully!');
            header("Location: " . baseUrl('student/academic.php'));
            exit;
        }
    }
}

// Fetch existing record
$stmt = $db->prepare("SELECT * FROM academic_records WHERE student_id = ?");
$stmt->execute([$studentId]);
$acad = $stmt->fetch() ?: [];

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="content-card">
            <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0"><i class="fas fa-graduation-cap me-2 text-primary"></i>Academic Performance Information</h5>
                    <small class="text-muted">Maintain your formal grades, attendance, and semester examination results</small>
                </div>
                <span class="badge bg-light text-dark border">Semester <?= $acad['current_semester'] ?? 1 ?></span>
            </div>
            <div class="content-card-body p-4">

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= baseUrl('student/academic.php') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <!-- Current College Performance -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Current College Standing</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label" for="current_cgpa">Current Cumulative CGPA (0 - 10) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" max="10" class="form-control" id="current_cgpa" name="current_cgpa" required value="<?= $acad['current_cgpa'] ?? '0.00' ?>">
                            <small class="text-muted">Scale 0.00 to 10.00</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="attendance_percentage">Overall Attendance % <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" min="0" max="100" class="form-control" id="attendance_percentage" name="attendance_percentage" required value="<?= $acad['attendance_percentage'] ?? '0.0' ?>">
                            <small class="text-muted">Target threshold: 75%+</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Current Semester <span class="text-danger">*</span></label>
                            <select class="form-select" name="current_semester" required>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($acad['current_semester'] ?? 1) == $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Active Backlogs <span class="text-danger">*</span></label>
                            <input type="number" min="0" class="form-control" name="num_backlogs" required value="<?= $acad['num_backlogs'] ?? 0 ?>">
                            <small class="text-muted">0 for clean record</small>
                        </div>
                    </div>

                    <!-- Prior School Foundation -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Prior Schooling / Diploma Baseline</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">10th Standard Percentage % <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" name="tenth_percentage" required value="<?= $acad['tenth_percentage'] ?? '0.00' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">12th Standard Percentage %</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" name="twelfth_percentage" value="<?= $acad['twelfth_percentage'] ?? '' ?>" placeholder="Optional if Diploma">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Polytechnic / Diploma % (if applicable)</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" name="diploma_percentage" value="<?= $acad['diploma_percentage'] ?? '' ?>" placeholder="Optional">
                        </div>
                    </div>

                    <!-- Semester-Wise SGPA Progression -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Semester-Wise GPA History (0 - 10)</h6>
                    <p class="text-muted small">Enter your Grade Point Averages for completed semesters to map your GPA trajectory curve.</p>
                    <div class="row g-3 mb-4">
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <div class="col-6 col-md-3">
                                <label class="form-label small">Semester <?= $s ?> GPA</label>
                                <input type="number" step="0.01" min="0" max="10" class="form-control form-control-sm" name="sem<?= $s ?>_gpa" value="<?= $acad["sem{$s}_gpa"] ?? '' ?>" placeholder="0.00">
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div class="text-muted small">
                            <i class="fas fa-bolt text-warning me-1"></i> Saving automatically recalculates your outcome scores.
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                            <i class="fas fa-arrows-rotate me-1"></i> Save & Recalculate Analysis
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
