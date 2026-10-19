<?php
/**
 * Student Profile View & Edit
 * Graduate Predicting Analysis System
 */

$pageTitle = 'My Profile';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

requireRole('student');
$currentUser = getCurrentUser();
$studentId = $currentUser['student_id'];
$db = getDB();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $dob      = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $gender   = $_POST['gender'] ?? 'Male';
        $degree   = trim($_POST['degree'] ?? 'B.Tech');
        $acadYear = trim($_POST['academic_year'] ?? '2023-2027');
        $gradYear = (int) ($_POST['graduation_year'] ?? 2027);

        if (empty($name)) {
            $error = 'Name cannot be empty.';
        } else {
            try {
                $db->beginTransaction();

                // Update users table
                $stmtU = $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
                $stmtU->execute([$name, $phone, $currentUser['id']]);

                // Update students table
                $stmtS = $db->prepare("
                    UPDATE students 
                    SET dob = ?, gender = ?, degree = ?, academic_year = ?, graduation_year = ?
                    WHERE id = ?
                ");
                $stmtS->execute([$dob, $gender, $degree, $acadYear, $gradYear, $studentId]);

                $db->commit();
                $_SESSION['user_name'] = $name;
                setFlash('success', 'Profile details updated successfully.');
                header("Location: " . baseUrl('student/profile.php'));
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Failed to update profile: ' . $e->getMessage();
            }
        }
    }
}

// Fetch current details
$stmt = $db->prepare("
    SELECT s.*, u.name, u.email, u.phone, d.dept_name, d.dept_code 
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="content-card">
            <div class="content-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0">Student Profile Information</h5>
                    <small class="text-muted">Review and update your personal and academic record identity</small>
                </div>
                <span class="badge bg-primary rounded-pill px-3 py-2 font-monospace">
                    ID: <?= sanitize($student['student_id']) ?>
                </span>
            </div>
            <div class="content-card-body p-4">
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= baseUrl('student/profile.php') ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required value="<?= sanitize($student['name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address (Login ID)</label>
                            <input type="email" class="form-control bg-light" value="<?= sanitize($student['email']) ?>" readonly>
                            <small class="text-muted">Email is fixed to protect your credentials.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" value="<?= sanitize($student['phone']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="dob" value="<?= sanitize($student['dob']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender">
                                <option value="Male" <?= $student['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $student['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $student['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Academic Program Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control bg-light" value="<?= sanitize($student['dept_name']) ?> (<?= sanitize($student['dept_code']) ?>)" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Degree Program</label>
                            <input type="text" class="form-control" name="degree" value="<?= sanitize($student['degree']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Academic Batch</label>
                            <input type="text" class="form-control" name="academic_year" value="<?= sanitize($student['academic_year']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Anticipated Graduation Year</label>
                            <input type="number" class="form-control" name="graduation_year" min="2024" max="2032" value="<?= sanitize($student['graduation_year']) ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= baseUrl('student/dashboard.php') ?>" class="btn btn-light rounded-pill px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                            <i class="fas fa-floppy-disk me-1"></i> Save Profile Changes
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
