<?php
/**
 * Administrator Faculty CRUD Management
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Manage Faculty Members';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

requireRole('admin');
$db = getDB();
$departments = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_name ASC")->fetchAll();

$error = '';

// Handle Delete Faculty
if (isset($_GET['delete'])) {
    $facId = (int) $_GET['delete'];
    $stmtU = $db->prepare("SELECT user_id FROM faculty WHERE id = ?");
    $stmtU->execute([$facId]);
    $userId = $stmtU->fetchColumn();

    if ($userId) {
        $stmtDel = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmtDel->execute([$userId]);
        setFlash('info', 'Faculty account deleted successfully.');
    }
    header("Location: " . baseUrl('admin/faculty.php'));
    exit;
}

// Handle Add Faculty
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security session expired.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $facId    = strtoupper(trim($_POST['faculty_id'] ?? ''));
        $deptId   = (int) ($_POST['department_id'] ?? 0);
        $desig    = trim($_POST['designation'] ?? 'Assistant Professor');

        if (empty($name) || empty($email) || empty($facId)) {
            $error = 'Name, email, and Faculty ID are required.';
        } else {
            $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = 'A user with this email address already exists.';
            } else {
                try {
                    $db->beginTransaction();

                    $pass = password_hash('Password@123', PASSWORD_BCRYPT);
                    $stmtU = $db->prepare("INSERT INTO users (name, email, password, role, phone, created_at) VALUES (?, ?, ?, 'faculty', ?, NOW())");
                    $stmtU->execute([$name, $email, $pass, $phone]);
                    $userId = (int) $db->lastInsertId();

                    $stmtF = $db->prepare("INSERT INTO faculty (user_id, faculty_id, department_id, designation, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmtF->execute([$userId, $facId, $deptId, $desig]);

                    $db->commit();
                    setFlash('success', "Faculty member {$name} added successfully with default login password: Password@123");
                    header("Location: " . baseUrl('admin/faculty.php'));
                    exit;

                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Failed to register faculty: ' . $e->getMessage();
                }
            }
        }
    }
}

// Fetch faculty records
$stmt = $db->query("
    SELECT f.id, f.faculty_id, f.designation,
           u.id as user_id, u.name, u.email, u.phone,
           d.dept_code, d.dept_name,
           (SELECT count(*) FROM students s WHERE s.department_id = f.department_id) as dept_students
    FROM faculty f
    JOIN users u ON f.user_id = u.id
    LEFT JOIN departments d ON f.department_id = d.id
    ORDER BY f.id ASC
");
$facultyList = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Title & Action Bar -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Faculty & Academic Mentors</h4>
        <p class="text-muted small mb-0">Departmental professors, career advisors, and student cohort mentor allocations</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
        <i class="fas fa-chalkboard-user me-1"></i> Add Faculty Member
    </button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small mb-4"><?= sanitize($error) ?></div>
<?php endif; ?>

<!-- Faculty Table -->
<div class="content-card">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Registered Faculty Staff (<?= count($facultyList) ?>)</h6>
        <span class="badge bg-light text-dark border">Default Password: Password@123</span>
    </div>
    <div class="content-card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-hover mb-0">
                <thead>
                    <tr>
                        <th>Faculty ID</th>
                        <th>Name & Email</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Cohort Size</th>
                        <th>Phone</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facultyList as $f): ?>
                        <tr>
                            <td><span class="font-monospace fw-bold text-dark"><?= sanitize($f['faculty_id']) ?></span></td>
                            <td>
                                <div class="fw-bold text-dark"><?= sanitize($f['name']) ?></div>
                                <small class="text-muted"><?= sanitize($f['email']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= sanitize($f['dept_name']) ?> (<?= sanitize($f['dept_code']) ?>)</span>
                            </td>
                            <td><?= sanitize($f['designation']) ?></td>
                            <td><strong><?= (int)$f['dept_students'] ?> Students</strong></td>
                            <td class="text-muted small"><?= sanitize($f['phone'] ?: 'N/A') ?></td>
                            <td>
                                <a href="<?= baseUrl('admin/faculty.php?delete=' . $f['id']) ?>" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 30px; height: 30px;" onclick="return confirm('Delete faculty member <?= sanitize($f['name']) ?>?');" title="Delete">
                                    <i class="fas fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Faculty Member -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-user-tie me-2 text-primary"></i>Register Faculty Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= baseUrl('admin/faculty.php') ?>">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g. Dr. Rajesh Sharma">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required placeholder="rajesh@college.edu">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Faculty Staff ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="faculty_id" required placeholder="e.g. FAC2024010">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department <span class="text-danger">*</span></label>
                        <select class="form-select" name="department_id" required>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= sanitize($d['dept_name']) ?> (<?= sanitize($d['dept_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Designation / Title</label>
                        <input type="text" class="form-control" name="designation" value="Assistant Professor" placeholder="e.g. Associate Professor, HOD">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" name="phone" placeholder="+91 98765 43210">
                    </div>

                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i> A secure default password (<code>Password@123</code>) will be created.
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Add Faculty Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
