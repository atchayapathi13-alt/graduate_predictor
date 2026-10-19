<?php
/**
 * Student Personalized Recommendations
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Personalized Recommendations';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../classes/RecommendationEngine.php';

requireRole('student');
$currentUser = getCurrentUser();
$studentId = $currentUser['student_id'];

$recEngine = new RecommendationEngine();
$recommendations = $recEngine->generateStudentRecommendations($studentId);

// Filter by category if selected
$selectedCat = $_GET['cat'] ?? 'all';
$filteredRecs = array_filter($recommendations, function ($r) use ($selectedCat) {
    if ($selectedCat === 'all') return true;
    return strtolower($r['category']) === strtolower($selectedCat);
});

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">Personalized Actionable Guidance</h4>
    <p class="text-muted small mb-0">Custom-tailored intervention roadmap designed to address specific profile gaps and accelerate placement readiness.</p>
</div>

<!-- Filter Pills -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="<?= baseUrl('student/recommendations.php?cat=all') ?>" class="btn btn-sm <?= $selectedCat === 'all' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
        All Recommendations (<?= count($recommendations) ?>)
    </a>
    <a href="<?= baseUrl('student/recommendations.php?cat=academic') ?>" class="btn btn-sm <?= $selectedCat === 'academic' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
        Academic
    </a>
    <a href="<?= baseUrl('student/recommendations.php?cat=technical') ?>" class="btn btn-sm <?= $selectedCat === 'technical' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
        Technical Skills
    </a>
    <a href="<?= baseUrl('student/recommendations.php?cat=internship') ?>" class="btn btn-sm <?= $selectedCat === 'internship' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
        Internship
    </a>
    <a href="<?= baseUrl('student/recommendations.php?cat=projects') ?>" class="btn btn-sm <?= $selectedCat === 'projects' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
        Projects
    </a>
    <a href="<?= baseUrl('student/recommendations.php?cat=certifications') ?>" class="btn btn-sm <?= $selectedCat === 'certifications' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
        Certifications
    </a>
    <a href="<?= baseUrl('student/recommendations.php?cat=soft_skills') ?>" class="btn btn-sm <?= $selectedCat === 'soft_skills' ? 'btn-primary' : 'btn-light border' ?> rounded-pill px-3">
        Soft Skills
    </a>
</div>

<!-- Recommendations List -->
<div class="row g-4 mb-4">
    <?php if (empty($filteredRecs)): ?>
        <div class="col-12">
            <div class="content-card p-5 text-center text-muted">
                <i class="fas fa-check-double text-success fs-1 mb-2"></i>
                <h5 class="fw-bold text-dark">No specific weaknesses flagged in this category</h5>
                <p class="mb-0">Your submitted parameters meet benchmark criteria. Maintain current consistency.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($filteredRecs as $rec): ?>
            <?php
                $borderClass = 'border-primary-subtle';
                $iconClass = 'fa-lightbulb text-primary';
                if ($rec['priority'] === 'High') {
                    $borderClass = 'border-danger-subtle';
                    $iconClass = 'fa-fire text-danger';
                } elseif ($rec['priority'] === 'Medium') {
                    $borderClass = 'border-warning-subtle';
                    $iconClass = 'fa-clock text-warning';
                }
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="content-card h-100 border <?= $borderClass ?> d-flex flex-column">
                    <div class="content-card-header d-flex justify-content-between align-items-center bg-light">
                        <span class="badge bg-white text-dark border font-monospace text-uppercase small">
                            <?= sanitize($rec['category']) ?>
                        </span>
                        <?= getPriorityBadge($rec['priority']) ?>
                    </div>
                    <div class="content-card-body p-4 flex-grow-1 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fas <?= $iconClass ?> fs-5"></i>
                                <h6 class="fw-bold text-dark mb-0"><?= sanitize($rec['title']) ?></h6>
                            </div>
                            <p class="text-muted small mb-3"><?= sanitize($rec['description']) ?></p>
                        </div>
                        <div class="pt-3 border-top mt-2">
                            <small class="text-secondary fw-semibold">
                                <i class="fas fa-arrow-turn-up me-1"></i> Impact: Elevates <?= ucfirst(sanitize($rec['category'])) ?> readiness index
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="disclaimer-box mb-4">
    <strong><i class="fas fa-info-circle me-1"></i> Mentorship Recommendation:</strong>
    Students are encouraged to review these action points during monthly advisory sessions with departmental faculty mentors.
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
