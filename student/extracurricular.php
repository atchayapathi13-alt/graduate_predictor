<?php
/**
 * Student Extracurricular Activities Management
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Extracurricular Activities';
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
    $actId = (int) $_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM extracurricular_activities WHERE id = ? AND student_id = ?");
    $stmtDel->execute([$actId, $studentId]);

    (new AnalysisEngine())->calculateStudentAnalysis($studentId);
    (new PredictionEngine())->predictStudentOutcome($studentId);
    (new RecommendationEngine())->generateStudentRecommendations($studentId);

    setFlash('info', 'Activity removed.');
    header("Location: " . baseUrl('student/extracurricular.php'));
    exit;
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security session expired.';
    } else {
        $type  = $_POST['activity_type'] ?? 'Hackathons';
        $title = trim($_POST['title'] ?? '');
        $level = trim($_POST['achievement_level'] ?? 'Participant');
        $date  = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
        $desc  = trim($_POST['description'] ?? '');

        if (empty($title)) {
            $error = 'Event / activity title is required.';
        } else {
            $stmtIns = $db->prepare("
                INSERT INTO extracurricular_activities (student_id, activity_type, title, achievement_level, event_date, description, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtIns->execute([$studentId, $type, $title, $level, $date, $desc]);

            (new AnalysisEngine())->calculateStudentAnalysis($studentId);
            (new PredictionEngine())->predictStudentOutcome($studentId);
            (new RecommendationEngine())->generateStudentRecommendations($studentId);

            setFlash('success', 'Extracurricular activity recorded and overall score updated!');
            header("Location: " . baseUrl('student/extracurricular.php'));
            exit;
        }
    }
}

// Fetch activities
$stmt = $db->prepare("SELECT * FROM extracurricular_activities WHERE student_id = ? ORDER BY id DESC");
$stmt->execute([$studentId]);
$activities = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <!-- Add Activity Form -->
    <div class="col-lg-5">
        <div class="content-card">
            <div class="content-card-header">
                <h6 class="fw-bold mb-0"><i class="fas fa-trophy me-2 text-primary"></i>Add Co-Curricular Activity</h6>
            </div>
            <div class="content-card-body p-4">

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= baseUrl('student/extracurricular.php') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label">Activity Category <span class="text-danger">*</span></label>
                        <select class="form-select" name="activity_type" required>
                            <option value="Hackathons">Hackathons</option>
                            <option value="Coding competitions">Coding Competitions</option>
                            <option value="Project exhibitions">Project Exhibitions</option>
                            <option value="Paper presentations">Paper Presentations</option>
                            <option value="Clubs">Technical / Cultural Clubs</option>
                            <option value="Workshops">Workshops & Bootcamps</option>
                            <option value="Sports">Sports & Athletics</option>
                            <option value="Volunteering">Volunteering & Community</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Event / Activity Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required placeholder="e.g. Smart India Hackathon, IEEE Paper Presentation">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Achievement / Role Level</label>
                        <input type="text" class="form-control" name="achievement_level" list="achieveList" placeholder="e.g. Winner - 1st Prize, Finalist, Participant">
                        <datalist id="achieveList">
                            <option value="Winner - 1st Prize">
                            <option value="Runner-up - 2nd Prize">
                            <option value="Finalist">
                            <option value="Lead Coordinator">
                            <option value="Participant">
                        </datalist>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Event Date</label>
                        <input type="date" class="form-control" name="event_date">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Brief Description of Contribution</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Summary of project exhibited, paper presented, or club leadership..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold">
                        <i class="fas fa-plus me-1"></i> Add Activity
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Existing Activities List -->
    <div class="col-lg-7">
        <div class="content-card">
            <div class="content-card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fas fa-medal me-2 text-success"></i>Co-Curricular Portfolio (<?= count($activities) ?>)</h6>
                <span class="badge bg-light text-dark border">Weighted in Extracurricular Score</span>
            </div>
            <div class="content-card-body p-0">
                <?php if (empty($activities)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-award fs-1 text-secondary opacity-50 mb-2"></i>
                        <p class="mb-0">No extracurricular activities recorded. Participation in hackathons, paper presentations, and clubs demonstrates teamwork and initiative.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Event & Category</th>
                                    <th>Achievement</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $a): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= sanitize($a['title']) ?></div>
                                            <span class="badge bg-light text-dark border"><?= sanitize($a['activity_type']) ?></span>
                                            <?php if (!empty($a['description'])): ?>
                                                <small class="text-muted d-block mt-1"><?= sanitize($a['description']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                                <?= sanitize($a['achievement_level']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small"><?= !empty($a['event_date']) ? date('M Y', strtotime($a['event_date'])) : '—' ?></td>
                                        <td>
                                            <a href="<?= baseUrl('student/extracurricular.php?delete=' . $a['id']) ?>" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 28px; height: 28px;" onclick="return confirm('Delete this record?');" title="Delete">
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
