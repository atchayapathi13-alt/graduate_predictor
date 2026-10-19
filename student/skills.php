<?php
/**
 * Student Skills Rating Management
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Technical & Soft Skills';
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
        $ratings = $_POST['skills'] ?? [];
        if (!empty($ratings) && is_array($ratings)) {
            $stmt = $db->prepare("
                INSERT INTO student_skills (student_id, skill_id, rating, updated_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE rating = VALUES(rating), updated_at = NOW()
            ");

            foreach ($ratings as $skillId => $val) {
                $ratingVal = max(1, min(5, (int)$val));
                $stmt->execute([$studentId, (int)$skillId, $ratingVal]);
            }

            // Recalculate
            $analysisEngine = new AnalysisEngine();
            $predEngine = new PredictionEngine();
            $recEngine = new RecommendationEngine();

            $analysisEngine->calculateStudentAnalysis($studentId);
            $predEngine->predictStudentOutcome($studentId);
            $recEngine->generateStudentRecommendations($studentId);

            setFlash('success', 'Technical and soft skill ratings updated successfully! Performance analytics refreshed.');
            header("Location: " . baseUrl('student/skills.php'));
            exit;
        }
    }
}

// Fetch all skills separated by category with student current ratings
$stmtTech = $db->prepare("
    SELECT s.id, s.name, s.category, COALESCE(ss.rating, 2) as current_rating
    FROM skills s
    LEFT JOIN student_skills ss ON s.id = ss.skill_id AND ss.student_id = ?
    WHERE s.category IN ('programming', 'technical')
    ORDER BY s.category, s.name ASC
");
$stmtTech->execute([$studentId]);
$technicalSkills = $stmtTech->fetchAll();

$stmtSoft = $db->prepare("
    SELECT s.id, s.name, s.category, COALESCE(ss.rating, 3) as current_rating
    FROM skills s
    LEFT JOIN student_skills ss ON s.id = ss.skill_id AND ss.student_id = ?
    WHERE s.category = 'soft_skill'
    ORDER BY s.name ASC
");
$stmtSoft->execute([$studentId]);
$softSkills = $stmtSoft->fetchAll();

$ratingLabels = [
    1 => '1 - Beginner',
    2 => '2 - Basic',
    3 => '3 - Intermediate',
    4 => '4 - Advanced',
    5 => '5 - Expert'
];

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-11">
        <div class="content-card">
            <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0"><i class="fas fa-code me-2 text-primary"></i>Skills Self-Assessment Rating</h5>
                    <small class="text-muted">Rate your technical proficiencies and interpersonal capabilities on a standard 1–5 scale</small>
                </div>
                <span class="badge bg-primary-subtle text-primary border px-3 py-2 rounded-pill">1: Beginner &rarr; 5: Expert</span>
            </div>
            <div class="content-card-body p-4">

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= baseUrl('student/skills.php') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <!-- Technical Skills Section -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-3">
                            <i class="fas fa-laptop-code text-primary fs-5"></i>
                            <h6 class="fw-bold text-dark mb-0">Technical & Programming Competencies</h6>
                        </div>

                        <div class="row g-3">
                            <?php foreach ($technicalSkills as $skill): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="p-3 bg-light rounded-3 border h-100 rating-container">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold small text-dark"><?= sanitize($skill['name']) ?></span>
                                            <span class="badge bg-secondary-subtle text-secondary small rounded-pill font-monospace">Rating: 1-5</span>
                                        </div>
                                        <input type="hidden" name="skills[<?= $skill['id'] ?>]" value="<?= $skill['current_rating'] ?>">
                                        <div class="rating-group d-flex justify-content-between">
                                            <?php for ($r = 1; $r <= 5; $r++): ?>
                                                <button type="button" class="rating-badge flex-grow-1 text-center <?= ($skill['current_rating'] == $r) ? 'active' : '' ?>" data-value="<?= $r ?>">
                                                    <?= $r ?>
                                                </button>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="text-muted small mt-1 text-center" style="font-size: 0.75rem;">
                                            <?= $ratingLabels[$skill['current_rating']] ?? '' ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Soft Skills Section -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-3">
                            <i class="fas fa-comments text-warning fs-5"></i>
                            <h6 class="fw-bold text-dark mb-0">Soft Skills & Communication Readiness</h6>
                        </div>

                        <div class="row g-3">
                            <?php foreach ($softSkills as $skill): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="p-3 bg-light rounded-3 border h-100 rating-container">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold small text-dark"><?= sanitize($skill['name']) ?></span>
                                            <span class="badge bg-secondary-subtle text-secondary small rounded-pill font-monospace">1-5</span>
                                        </div>
                                        <input type="hidden" name="skills[<?= $skill['id'] ?>]" value="<?= $skill['current_rating'] ?>">
                                        <div class="rating-group d-flex justify-content-between">
                                            <?php for ($r = 1; $r <= 5; $r++): ?>
                                                <button type="button" class="rating-badge flex-grow-1 text-center <?= ($skill['current_rating'] == $r) ? 'active' : '' ?>" data-value="<?= $r ?>">
                                                    <?= $r ?>
                                                </button>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="text-muted small mt-1 text-center" style="font-size: 0.75rem;">
                                            <?= $ratingLabels[$skill['current_rating']] ?? '' ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div class="text-muted small">
                            <i class="fas fa-circle-info text-primary me-1"></i> Ratings immediately recalculate Technical and Soft Skills radar graphs.
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                            <i class="fas fa-floppy-disk me-1"></i> Save Skills & Recalculate
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
