<?php
/**
 * Administrator Student CRUD Management
 * Graduate Predicting Analysis System
 */

$pageTitle = 'Manage Students';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../classes/AnalysisEngine.php';
require_once __DIR__ . '/../classes/PredictionEngine.php';

requireRole('admin');
$db = getDB();
$departments = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_name ASC")->fetchAll();

$error = '';

// Handle Delete Student
if (isset($_GET['delete'])) {
    $studentId = (int) $_GET['delete'];
    $stmtUser = $db->prepare("SELECT user_id FROM students WHERE id = ?");
    $stmtUser->execute([$studentId]);
    $userId = $stmtUser->fetchColumn();

    if ($userId) {
        $stmtDel = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmtDel->execute([$userId]);
        setFlash('info', 'Student account and associated records deleted successfully.');
    }
    header("Location: " . baseUrl('admin/students.php'));
    exit;
}

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        $error = 'Security session expired.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $stuId    = strtoupper(trim($_POST['student_id'] ?? ''));
        $deptId   = (int) ($_POST['department_id'] ?? 0);
        $degree   = trim($_POST['degree'] ?? 'B.Tech');
        $cgpa     = (float) ($_POST['cgpa'] ?? 7.0);
        $attend   = (float) ($_POST['attendance'] ?? 75.0);

        if (empty($name) || empty($email) || empty($stuId)) {
            $error = 'Name, email, and Student ID are required.';
        } else {
            // Check duplicates
            $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = 'Email already in use.';
            } else {
                try {
                    $db->beginTransaction();

                    $pass = password_hash('Password@123', PASSWORD_BCRYPT);
                    $stmtU = $db->prepare("INSERT INTO users (name, email, password, role, phone, created_at) VALUES (?, ?, ?, 'student', ?, NOW())");
                    $stmtU->execute([$name, $email, $pass, $phone]);
                    $userId = (int) $db->lastInsertId();

                    $stmtS = $db->prepare("INSERT INTO students (user_id, student_id, department_id, degree, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmtS->execute([$userId, $stuId, $deptId, $degree]);
                    $newStudentId = (int) $db->lastInsertId();

                    $stmtA = $db->prepare("INSERT INTO academic_records (student_id, current_cgpa, attendance_percentage, tenth_percentage, twelfth_percentage) VALUES (?, ?, ?, 75.0, 75.0)");
                    $stmtA->execute([$newStudentId, $cgpa, $attend]);

                    // Initialize skills
                    $allSkills = $db->query("SELECT id FROM skills")->fetchAll(PDO::FETCH_COLUMN);
                    $stmtSkill = $db->prepare("INSERT INTO student_skills (student_id, skill_id, rating) VALUES (?, ?, 2)");
                    foreach ($allSkills as $skId) {
                        $stmtSkill->execute([$newStudentId, $skId]);
                    }

                    $db->commit();

                    (new AnalysisEngine())->calculateStudentAnalysis($newStudentId);
                    (new PredictionEngine())->predictStudentOutcome($newStudentId);

                    setFlash('success', "Student {$name} ({$stuId}) added successfully with default password: Password@123");
                    header("Location: " . baseUrl('admin/students.php'));
                    exit;

                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Failed to create student: ' . $e->getMessage();
                }
            }
        }
    }
}

// Fetch all students
$search = trim($_GET['search'] ?? '');
$where = "1=1";
$params = [];
if (!empty($search)) {
    $where = "(u.name LIKE ? OR s.student_id LIKE ? OR u.email LIKE ?)";
    $t = "%$search%";
    $params = [$t, $t, $t];
}

$stmt = $db->prepare("
    SELECT s.id, s.student_id, s.degree, s.academic_year,
           u.id as user_id, u.name, u.email, u.phone,
           d.dept_code, d.dept_name,
           ar.current_cgpa, ar.attendance_percentage, ar.num_backlogs,
           pa.overall_score, pr.category as prediction_category
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    LEFT JOIN performance_analysis pa ON s.id = pa.student_id
    LEFT JOIN predictions pr ON s.id = pr.student_id
    WHERE $where
    ORDER BY s.id ASC
");
$stmt->execute($params);
$students = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Title & Action Bar -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Student Accounts & Profiles</h4>
        <p class="text-muted small mb-0">Centralized database management of student credentials, program enrollments, and outcome statuses</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#addStudentModal">
            <i class="fas fa-user-plus me-1"></i> Add New Student
        </button>
        <a href="<?= baseUrl('faculty/export.php') ?>" class="btn btn-outline-success rounded-pill px-3">
            <i class="fas fa-file-csv me-1"></i> Export
        </a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small mb-4"><?= sanitize($error) ?></div>
<?php endif; ?>

<!-- Search Card -->
<div class="content-card mb-4 p-3">
    <form method="GET" action="<?= baseUrl('admin/students.php') ?>" class="row g-2 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" name="search" placeholder="Search by name, roll no, or email..." value="<?= sanitize($search) ?>">
            </div>
        </div>
        <div class="col-md-3 text-end">
            <button type="submit" class="btn btn-primary rounded-pill w-100 fw-semibold">Search</button>
        </div>
    </form>
</div>

<!-- Students Table -->
<div class="content-card">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Registered Students (<?= count($students) ?>)</h6>
        <span class="badge bg-light text-dark border">Default Password: Password@123</span>
    </div>
    <div class="content-card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name & Email</th>
                        <th>Department</th>
                        <th>CGPA</th>
                        <th>Attendance</th>
                        <th>Overall Score</th>
                        <th>Outcome Tier</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td><span class="font-monospace fw-bold text-dark"><?= sanitize($s['student_id']) ?></span></td>
                            <td>
                                <div class="fw-bold text-dark"><?= sanitize($s['name']) ?></div>
                                <small class="text-muted"><?= sanitize($s['email']) ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= sanitize($s['dept_code']) ?></span></td>
                            <td><strong><?= number_format($s['current_cgpa'], 2) ?></strong></td>
                            <td>
                                <span class="<?= $s['attendance_percentage'] < 75 ? 'text-danger fw-bold' : 'text-success' ?>">
                                    <?= number_format($s['attendance_percentage'], 1) ?>%
                                </span>
                            </td>
                            <td><strong><?= number_format($s['overall_score'], 1) ?>%</strong></td>
                            <td><?= getReadinessBadge($s['prediction_category'] ?? 'Needs Improvement') ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= baseUrl('faculty/student_detail.php?id=' . $s['id']) ?>" class="btn btn-sm btn-outline-primary rounded-circle p-1" style="width: 30px; height: 30px;" title="View Analysis">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= baseUrl('admin/students.php?delete=' . $s['id']) ?>" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 30px; height: 30px;" onclick="return confirm('Permanently delete student <?= sanitize($s['name']) ?> and all academic history?');" title="Delete">
                                        <i class="fas fa-trash-can"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add New Student -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-user-plus me-2 text-primary"></i>Register New Student Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= baseUrl('admin/students.php') ?>">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="add">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required placeholder="e.g. John Doe">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" required placeholder="john@college.edu">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Student ID (Roll No) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="student_id" required placeholder="e.g. STU2024101">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" placeholder="+91 98765 43210">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select" name="department_id" required>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= sanitize($d['dept_name']) ?> (<?= sanitize($d['dept_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Degree Program</label>
                            <input type="text" class="form-control" name="degree" value="B.Tech CSE">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Initial Cumulative CGPA</label>
                            <input type="number" step="0.01" min="0" max="10" class="form-control" name="cgpa" value="7.50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Initial Attendance %</label>
                            <input type="number" step="0.1" min="0" max="100" class="form-control" name="attendance" value="82.0">
                        </div>
                    </div>
                    <div class="text-muted small mt-3">
                        <i class="fas fa-info-circle me-1"></i> A secure password (<code>Password@123</code>) will be set automatically.
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Save Student Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
