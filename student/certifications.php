<?php
/**
 * Student Certifications Management
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Certifications';
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

// Handle Delete
if (isset($_GET['delete'])) {
    $certId = (int) $_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM certifications WHERE id = ? AND student_id = ?");
    $stmtDel->execute([$certId, $studentId]);

    // Recalculate
    (new AnalysisEngine())->calculateStudentAnalysis($studentId);
    (new PredictionEngine())->predictStudentOutcome($studentId);
    (new RecommendationEngine())->generateStudentRecommendations($studentId);

    setFlash('info', 'Certification removed.');
    header("Location: " . baseUrl('student/certifications.php'));
    exit;
}

// Handle Add New
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security session expired.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $provider = trim($_POST['provider'] ?? '');
        $date     = !empty($_POST['issue_date']) ? $_POST['issue_date'] : null;
        $level    = $_POST['certification_level'] ?? 'Intermediate';
        $url      = trim($_POST['credential_url'] ?? '');

        if (empty($name) || empty($provider)) {
            $error = 'Certification name and provider are required.';
        } else {
            $stmtIns = $db->prepare("
                INSERT INTO certifications (student_id, name, provider, issue_date, certification_level, credential_url, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtIns->execute([$studentId, $name, $provider, $date, $level, $url]);

            // Recalculate
            (new AnalysisEngine())->calculateStudentAnalysis($studentId);
            (new PredictionEngine())->predictStudentOutcome($studentId);
            (new RecommendationEngine())->generateStudentRecommendations($studentId);

            setFlash('success', 'New certification recorded and analysis updated!');
            header("Location: " . baseUrl('student/certifications.php'));
            exit;
        }
    }
}

// Fetch existing certs
$stmt = $db->prepare("SELECT * FROM certifications WHERE student_id = ? ORDER BY id DESC");
$stmt->execute([$studentId]);
$certs = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <!-- Add Certification Form -->
    <div class="col-lg-5">
        <div class="content-card">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Certification</h6>
            </div>
            <div class="content-card-body p-4">
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= baseUrl('student/certifications.php') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label">Certification Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g. AWS Solutions Architect">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Issuing Provider <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="provider" list="providersList" required placeholder="e.g. NPTEL, AWS, Coursera">
                        <datalist id="providersList">
                            <option value="NPTEL">
                            <option value="Coursera">
                            <option value="AWS">
                            <option value="Google">
                            <option value="Microsoft">
                            <option value="Infosys Springboard">
                            <option value="IBM">
                            <option value="Oracle">
                        </datalist>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Certification Level</label>
                        <select class="form-select" name="certification_level">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate" selected>Intermediate</option>
                            <option value="Advanced">Advanced</option>
                            <option value="Professional">Professional</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Issue Date</label>
                        <input type="date" class="form-control" name="issue_date">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Verification / Credential URL</label>
                        <input type="url" class="form-control" name="credential_url" placeholder="https://coursera.org/verify/...">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold">
                        <i class="fas fa-certificate me-1"></i> Add Credential
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Existing Certifications List -->
    <div class="col-lg-7">
        <div class="content-card">
            <div class="content-card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fas fa-list-check me-2 text-success"></i>My Certifications (<?= count($certs) ?>)</h6>
                <span class="badge bg-light text-dark border">Weighted in Tech Score</span>
            </div>
            <div class="content-card-body p-0">
                <?php if (empty($certs)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-certificate fs-1 text-secondary opacity-50 mb-2"></i>
                        <p class="mb-0">No certifications recorded yet. Adding verified credentials significantly boosts your technical readiness score.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Credential</th>
                                    <th>Provider</th>
                                    <th>Level</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($certs as $c): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= sanitize($c['name']) ?></div>
                                            <?php if (!empty($c['credential_url'])): ?>
                                                <a href="<?= sanitize($c['credential_url']) ?>" target="_blank" rel="noopener" class="small text-primary text-decoration-none">
                                                    <i class="fas fa-arrow-up-right-from-square me-1"></i> Verify
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= sanitize($c['provider']) ?></span></td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                                                <?= sanitize($c['certification_level']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small"><?= !empty($c['issue_date']) ? date('M Y', strtotime($c['issue_date'])) : '—' ?></td>
                                        <td>
                                            <a href="<?= baseUrl('student/certifications.php?delete=' . $c['id']) ?>" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 28px; height: 28px;" onclick="return confirm('Remove this certification?');" title="Delete">
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
