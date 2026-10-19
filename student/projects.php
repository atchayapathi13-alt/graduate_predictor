<?php
/**
 * Student Projects Management
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Projects Portfolio';
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
    $projId = (int) $_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM projects WHERE id = ? AND student_id = ?");
    $stmtDel->execute([$projId, $studentId]);

    (new AnalysisEngine())->calculateStudentAnalysis($studentId);
    (new PredictionEngine())->predictStudentOutcome($studentId);
    (new RecommendationEngine())->generateStudentRecommendations($studentId);

    setFlash('info', 'Project removed.');
    header("Location: " . baseUrl('student/projects.php'));
    exit;
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security session expired.';
    } else {
        $title   = trim($_POST['title'] ?? '');
        $domain  = trim($_POST['domain'] ?? '');
        $tech    = trim($_POST['technologies'] ?? '');
        $complex = $_POST['complexity'] ?? 'Intermediate';
        $status  = $_POST['status'] ?? 'Completed';
        $url     = trim($_POST['project_url'] ?? '');

        if (empty($title) || empty($domain) || empty($tech)) {
            $error = 'Project title, domain, and technologies used are required.';
        } else {
            $stmtIns = $db->prepare("
                INSERT INTO projects (student_id, title, domain, technologies, complexity, status, project_url, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtIns->execute([$studentId, $title, $domain, $tech, $complex, $status, $url]);

            (new AnalysisEngine())->calculateStudentAnalysis($studentId);
            (new PredictionEngine())->predictStudentOutcome($studentId);
            (new RecommendationEngine())->generateStudentRecommendations($studentId);

            setFlash('success', 'Project added and technical portfolio score refreshed!');
            header("Location: " . baseUrl('student/projects.php'));
            exit;
        }
    }
}

// Fetch existing projects
$stmt = $db->prepare("SELECT * FROM projects WHERE student_id = ? ORDER BY id DESC");
$stmt->execute([$studentId]);
$projects = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <!-- Add Project Form -->
    <div class="col-lg-5">
        <div class="content-card">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-diagram-project me-2 text-primary"></i>Add Engineering Project</h6>
            </div>
            <div class="content-card-body p-4">

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= baseUrl('student/projects.php') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label">Project Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required placeholder="e.g. Distributed Cloud Optimizer">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Domain / Field <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="domain" required placeholder="e.g. Cloud Computing, AI, Web Dev">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Technologies & Frameworks <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="technologies" required placeholder="e.g. React, Node.js, Docker, MySQL">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Complexity Level</label>
                            <select class="form-select" name="complexity">
                                <option value="Basic">Basic</option>
                                <option value="Intermediate" selected>Intermediate</option>
                                <option value="Advanced">Advanced (Capstone)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="Completed" selected>Completed</option>
                                <option value="Ongoing">Ongoing</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">GitHub / Live Demo URL</label>
                        <input type="url" class="form-control" name="project_url" placeholder="https://github.com/username/repo">
                        <small class="text-muted">Documented repositories boost career readiness evaluation.</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold">
                        <i class="fas fa-plus me-1"></i> Add Project
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Existing Projects List -->
    <div class="col-lg-7">
        <div class="content-card">
            <div class="content-card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fas fa-folder-tree me-2 text-success"></i>Project Repository (<?= count($projects) ?>)</h6>
                <span class="badge bg-light text-dark border">Weighted in Tech Score (30%)</span>
            </div>
            <div class="content-card-body p-0">
                <?php if (empty($projects)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-folder-open fs-1 text-secondary opacity-50 mb-2"></i>
                        <p class="mb-0">No practical projects registered. Having at least 2 well-documented projects is strongly recommended for placement readiness.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Project Title & Tech</th>
                                    <th>Domain</th>
                                    <th>Complexity</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= sanitize($p['title']) ?></div>
                                            <small class="text-muted d-block"><?= sanitize($p['technologies']) ?></small>
                                            <?php if (!empty($p['project_url'])): ?>
                                                <a href="<?= sanitize($p['project_url']) ?>" target="_blank" rel="noopener" class="small text-primary text-decoration-none">
                                                    <i class="fab fa-github me-1"></i> Repository Link
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= sanitize($p['domain']) ?></span></td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                                                <?= sanitize($p['complexity']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= ($p['status'] === 'Completed') ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' ?> rounded-pill">
                                                <?= sanitize($p['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= baseUrl('student/projects.php?delete=' . $p['id']) ?>" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 28px; height: 28px;" onclick="return confirm('Delete this project?');" title="Delete">
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
