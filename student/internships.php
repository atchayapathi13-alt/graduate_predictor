<?php
/**
 * Student Internships Management
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Internship Information';
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

// Handle Delete
if (isset($_GET['delete'])) {
    $internId = (int) $_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM internships WHERE id = ? AND student_id = ?");
    $stmtDel->execute([$internId, $studentId]);

    (new AnalysisEngine())->calculateStudentAnalysis($studentId);
    (new PredictionEngine())->predictStudentOutcome($studentId);
    (new RecommendationEngine())->generateStudentRecommendations($studentId);

    setFlash('info', 'Internship record removed.');
    header("Location: " . baseUrl('student/internships.php'));
    exit;
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security session expired.';
    } else {
        $hasInternship = (int) ($_POST['has_internship'] ?? 1);
        $company  = trim($_POST['company'] ?? '');
        $duration = (int) ($_POST['duration_months'] ?? 1);
        $domain   = trim($_POST['domain'] ?? '');
        $type     = $_POST['internship_type'] ?? 'Full-time';
        $skills   = trim($_POST['skills_gained'] ?? '');

        if ($hasInternship === 1 && (empty($company) || empty($domain))) {
            $error = 'Please provide the company name and domain for the completed internship.';
        } else {
            $stmtIns = $db->prepare("
                INSERT INTO internships (student_id, has_internship, company, duration_months, domain, internship_type, skills_gained, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtIns->execute([$studentId, $hasInternship, $company, $duration, $domain, $type, $skills]);

            (new AnalysisEngine())->calculateStudentAnalysis($studentId);
            (new PredictionEngine())->predictStudentOutcome($studentId);
            (new RecommendationEngine())->generateStudentRecommendations($studentId);

            setFlash('success', 'Internship experience recorded and career readiness score updated!');
            header("Location: " . baseUrl('student/internships.php'));
            exit;
        }
    }
}

// Fetch existing internships
$stmt = $db->prepare("SELECT * FROM internships WHERE student_id = ? ORDER BY id DESC");
$stmt->execute([$studentId]);
$internships = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <!-- Add Internship Form -->
    <div class="col-lg-5">
        <div class="content-card">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-briefcase me-2 text-primary"></i>Record Internship Experience</h6>
            </div>
            <div class="content-card-body p-4">

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= baseUrl('student/internships.php') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="has_internship" value="1">

                    <div class="mb-3">
                        <label class="form-label">Company / Organization <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="company" required placeholder="e.g. Infosys, TCS, Startup Labs">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Domain / Field <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="domain" required placeholder="e.g. Cloud Infrastructure, Web Development, AI/ML">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Duration (Months)</label>
                            <input type="number" min="1" max="24" class="form-control" name="duration_months" value="2" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Internship Type</label>
                            <select class="form-select" name="internship_type">
                                <option value="Full-time">Full-time</option>
                                <option value="Part-time">Part-time</option>
                                <option value="Virtual">Virtual / Remote</option>
                                <option value="Research">Research Lab</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Key Skills & Tools Applied</label>
                        <textarea class="form-control" name="skills_gained" rows="3" placeholder="e.g. Docker, Python FastAPI, PostgreSQL, Agile Scrum..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold">
                        <i class="fas fa-plus me-1"></i> Add Internship
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Existing Internships List -->
    <div class="col-lg-7">
        <div class="content-card">
            <div class="content-card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fas fa-building me-2 text-success"></i>Recorded Internships (<?= count($internships) ?>)</h6>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Key Readiness Factor</span>
            </div>
            <div class="content-card-body p-0">
                <?php if (empty($internships)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-briefcase fs-1 text-secondary opacity-50 mb-2"></i>
                        <p class="mb-0">No internship records added yet. Completing an internship provides a 40-point boost toward your career readiness score.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Domain</th>
                                    <th>Duration</th>
                                    <th>Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($internships as $i): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= sanitize($i['company']) ?></div>
                                            <?php if (!empty($i['skills_gained'])): ?>
                                                <small class="text-muted d-block text-truncate" style="max-width: 200px;">
                                                    <?= sanitize($i['skills_gained']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= sanitize($i['domain']) ?></span></td>
                                        <td><?= (int)$i['duration_months'] ?> Months</td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                                                <?= sanitize($i['internship_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= baseUrl('student/internships.php?delete=' . $i['id']) ?>" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 28px; height: 28px;" onclick="return confirm('Delete this internship record?');" title="Delete">
                                                <i class="fas fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
